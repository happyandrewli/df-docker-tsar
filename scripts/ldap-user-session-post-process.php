
#####
## Update the following three variables before running script
wing three variables before running script
#####

# The base URL of your DreamFactory installation
$baseUrl = 'http://cats6-84.it.census.gov/api/v2/';

# An administrative-level token (full access to system and user APIs)
$dreamfactoryAdminToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJlYjhmZmFjY2FiZTUzYjIxNzYwMDBlZTYzYzU4YzA5MyIsImlzcyI6Imh0dHA6Ly9jYXRzNi04NC5pdC5jZW5zdXMuZ292L2FwaS92Mi9zeXN0ZW0vYWRtaW4vc2Vzc2lvbiIsImlhdCI6MTU5NDcyNzYxOCwiZXhwIjoxNTk0ODE0MDE4LCJuYmYiOjE1OTQ3Mjc2MTgsImp0aSI6InNCV1JRSnhNYVhBSlM2dVkiLCJ1c2VyX2lkIjoxNiwiZm9yZXZlciI6ZmFsc2V9.egubz-GxwI7Pja7LCOKHKQMsppQCxCS7z1oMQb8zR5g';


# The namespace of the service containing the tables with names matching the LDAP groups
$databases = ['postgresql'];



$groupMembership = Arr::get($event, 'response.content.groupMembership');


### Abort if no groups
if (count($groupMembership) === 0) {
    return;
}

$options = [];
$options['headers'] = [];
$options['headers']['X-DreamFactory-Api-Key'] = $platform['session']['api_key'];
$options['headers']['X-DreamFactory-Session-Token'] = $dreamfactoryAdminToken;
$options['headers']['Content-Type'] = 'application/json';

$api = $platform["api"];
$post = $api->post;
$get = $api->get;
$put = $api->put;


### Getting ids of database services
$databaseServicesIds = [];
$url = $baseUrl.'system/service?filter=name=';
foreach ($databases as $db) {
    $url = $url . $db;
    $result = $get($url,null, $options);
    $databaseServicesIds [$db] = Arr::get($get($url, null, $options), 'content.resource')[0]['id'];
}

### Extracting cn={table-name}
$tableNames = [];
$roleName = '';


foreach ($groupMembership as $dn) {
    preg_match('/cn=(.*?),/', $dn, $matches, PREG_OFFSET_CAPTURE);
    $n = $matches[1][0];
    $tableNames [] = strtolower($n);
}

### Defining role_service_access based on groupMembership
$role_service_access_by_role_id = [];
foreach ($tableNames as $tableName) {
    foreach ($databaseServicesIds as $databaseService=>$id) {
        $url = $baseUrl."$databaseService/_table/$tableName";
        $result = $get($url,null, $options);

        if (isset($result['status_code']) && $result['status_code'] < 400) {
            $roleName = empty($roleName) ? $tableName : $roleName.'+'.$tableName;
            $roleAccess = [
                "verb_mask" => 31,
                "requestor_mask" => 3,
                "component" => "_table/$tableName/*",
                "service_id" => $id,
                "filters" => [],
                "filter_op" => "AND"
            ];
            $role_service_access_by_role_id [] = $roleAccess;
        } elseif (isset($result['status_code']) && $result['status_code'] === 404) {
            continue; 
        } else {
            throw new \Exception("error code = " . $result["content"]["error"]["code"] . ", messsage = " . $result["content"]["error"]["message"]); 
        }
    }
};

### Defining future role name
$roleName = substr($roleName, -1) === '+' ? substr($roleName, 0, strlen($roleName) - 1) : $roleName;

### Determine if such role already exists
$url = $baseUrl.'system/role?fields=*&related=role_service_access_by_role_id';
$existingRoles =  Arr::get($get($url,null, $options), 'content.resource');
$role_with_same_access = null;
### Go through each role
foreach ($existingRoles as $role) {
    if (isset($role['role_service_access_by_role_id']) && count($role['role_service_access_by_role_id']) === count($role_service_access_by_role_id)) {
### Compare the existing role service access to groupMembership one
        foreach ($role_service_access_by_role_id as $roleAccess) {
            $isPresent = false;
            foreach ($role['role_service_access_by_role_id'] as $existingRoleAccess) {
                $condition = 
                in_array($roleAccess['component'], $existingRoleAccess) &&
                in_array($roleAccess['service_id'], $existingRoleAccess) &&
                in_array($roleAccess['verb_mask'], $existingRoleAccess);
                if($condition) {                    
                    $isPresent=true;
                    continue;
                }
            }

        }
        if($isPresent === false) {
            continue;
        } else {
           $role_with_same_access = $role;
           ### Role with the same access exists get out of the loop
           break;
        }
    }
}

### Maintaining the new role or assigning the existing one
$roleId = null;
if (empty($role_with_same_access)) {
    ### Create new role
    $payload = [
        "resource" => [
        [
            "name"=> $roleName,
            "description"=> $roleName,
            "is_active"=> true,
            "default_app_id"=> null,
            "role_service_access_by_role_id"=> $role_service_access_by_role_id,
            "id"=> null
        ]]];

    $result = $post($url, json_encode($payload, JSON_UNESCAPED_SLASHES), $options);
    if(isset($result['status_code']) && $result['status_code'] < 400) {
        $roleId = Arr::get($result, 'content.resource')[0]['id'];
    } else {
        throw new \Exception("error code = " . $result["content"]["error"]["code"] . ", messsage = " . $result["content"]["error"]["message"]); 
    }
} else {
    ### Use existing role
    $roleId = $role_with_same_access['id'];
}


### Assigning the role to the user
$userId = Arr::get($platform,'session.user.id');
$url = $baseUrl.'system/app/?fields=id,name';
$apps = Arr::get($get($url,null, $options), 'content.resource');
$user_to_app_to_role_by_user_id = [];
foreach ($apps as $app) {
    ### Defining user_to_app_to_role_by_user_id
    $user_to_app_to_role_by_user_id [] = [
        'app_id' => $app['id'],
        'role_id' => $roleId,
        'user_id' => $userId
        ];
}

## Updating the user
$url = $baseUrl."system/user/$userId?fields=*&related=user_to_app_to_role_by_user_id";
$userData = Arr::get($get($url,null, $options), 'content');
if (count($userData['user_to_app_to_role_by_user_id']) === 0) {
    $userData['user_to_app_to_role_by_user_id'] = $user_to_app_to_role_by_user_id; 
} else {
    foreach ($userData['user_to_app_to_role_by_user_id'] as $userAppRole) {
        foreach ($user_to_app_to_role_by_user_id as &$newuserAppRole) {
            if ($userAppRole['app_id'] === $newuserAppRole['app_id']) {
                $newuserAppRole['id'] = $userAppRole['id'];
            }
        }
    }
    $userData['user_to_app_to_role_by_user_id'] = $user_to_app_to_role_by_user_id; 
}

### Assign the role to user
$result = $put($url, json_encode($userData, JSON_UNESCAPED_SLASHES), $options);

if(isset($result['status_code']) && $result['status_code'] < 400) {
    var_dump(print_r($result));
} else {
    throw new \Exception("error code = " . $result["content"]["error"]["code"] . ", messsage = " . $result["content"]["error"]["message"]); 
}






#####

# The base URL of your DreamFactory installation
$baseUrl = 'http://cats6-84.it.census.gov/api/v2/';

# An administrative-level token (full access to system and user APIs)
$dreamfactoryAdminToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJlYjhmZmFjY2FiZTUzYjIxNzYwMDBlZTYzYzU4YzA5MyIsImlzcyI6Imh0dHA6Ly9jYXRzNi04NC5pdC5jZW5zdXMuZ292L2FwaS92Mi9zeXN0ZW0vYWRtaW4vc2Vzc2lvbiIsImlhdCI6MTU5NDcyNzYxOCwiZXhwIjoxNTk0ODE0MDE4LCJuYmYiOjE1OTQ3Mjc2MTgsImp0aSI6InNCV1JRSnhNYVhBSlM2dVkiLCJ1c2VyX2lkIjoxNiwiZm9yZXZlciI6ZmFsc2V9.egubz-GxwI7Pja7LCOKHKQMsppQCxCS7z1oMQb8zR5g';

# The namespace of the service containing the tables with names matching the LDAP groups
$databases = ['postgresql'];


$groupMembership = Arr::get($event, 'response.content.groupMembership');


### Abort if no groups
if (count($groupMembership) === 0) {
    return;
}

$options = [];
$options['headers'] = [];
$options['headers']['X-DreamFactory-Api-Key'] = $platform['session']['api_key'];
$options['headers']['X-DreamFactory-Session-Token'] = $dreamfactoryAdminToken;
$options['headers']['Content-Type'] = 'application/json';

$api = $platform["api"];
$post = $api->post;
$get = $api->get;
$put = $api->put;


### Getting ids of database services
$databaseServicesIds = [];
$url = $baseUrl.'system/service?filter=name=';
foreach ($databases as $db) {
    $url = $url . $db;
    $result = $get($url,null, $options);
    $databaseServicesIds [$db] = Arr::get($get($url, null, $options), 'content.resource')[0]['id'];
}

### Extracting cn={table-name}
$tableNames = [];
$roleName = '';


foreach ($groupMembership as $dn) {
    preg_match('/cn=(.*?),/', $dn, $matches, PREG_OFFSET_CAPTURE);
    $n = $matches[1][0];
    $tableNames [] = strtolower($n);
}

### Defining role_service_access based on groupMembership
$role_service_access_by_role_id = [];
foreach ($tableNames as $tableName) {
    foreach ($databaseServicesIds as $databaseService=>$id) {
        $url = $baseUrl."$databaseService/_table/$tableName";
        $result = $get($url,null, $options);

        if (isset($result['status_code']) && $result['status_code'] < 400) {
            $roleName = empty($roleName) ? $tableName : $roleName.'+'.$tableName;
            $roleAccess = [
                "verb_mask" => 31,
                "requestor_mask" => 3,
                "component" => "_table/$tableName/*",
                "service_id" => $id,
                "filters" => [],
                "filter_op" => "AND"
            ];
            $role_service_access_by_role_id [] = $roleAccess;
        } elseif (isset($result['status_code']) && $result['status_code'] === 404) {
            continue; 
        } else {
            throw new \Exception("error code = " . $result["content"]["error"]["code"] . ", messsage = " . $result["content"]["error"]["message"]); 
        }
    }
};

### Defining future role name
$roleName = substr($roleName, -1) === '+' ? substr($roleName, 0, strlen($roleName) - 1) : $roleName;

### Determine if such role already exists
$url = $baseUrl.'system/role?fields=*&related=role_service_access_by_role_id';
$existingRoles =  Arr::get($get($url,null, $options), 'content.resource');
$role_with_same_access = null;
### Go through each role
foreach ($existingRoles as $role) {
    if (isset($role['role_service_access_by_role_id']) && count($role['role_service_access_by_role_id']) === count($role_service_access_by_role_id)) {
### Compare the existing role service access to groupMembership one
        foreach ($role_service_access_by_role_id as $roleAccess) {
            $isPresent = false;
            foreach ($role['role_service_access_by_role_id'] as $existingRoleAccess) {
                $condition = 
                in_array($roleAccess['component'], $existingRoleAccess) &&
                in_array($roleAccess['service_id'], $existingRoleAccess) &&
                in_array($roleAccess['verb_mask'], $existingRoleAccess);
                if($condition) {                    
                    $isPresent=true;
                    continue;
                }
            }

        }
        if($isPresent === false) {
            continue;
        } else {
           $role_with_same_access = $role;
           ### Role with the same access exists get out of the loop
           break;
        }
    }
}

### Maintaining the new role or assigning the existing one
$roleId = null;
if (empty($role_with_same_access)) {
    ### Create new role
    $payload = [
        "resource" => [
        [
            "name"=> $roleName,
            "description"=> $roleName,
            "is_active"=> true,
            "default_app_id"=> null,
            "role_service_access_by_role_id"=> $role_service_access_by_role_id,
            "id"=> null
        ]]];

    $result = $post($url, json_encode($payload, JSON_UNESCAPED_SLASHES), $options);
    if(isset($result['status_code']) && $result['status_code'] < 400) {
        $roleId = Arr::get($result, 'content.resource')[0]['id'];
    } else {
        throw new \Exception("error code = " . $result["content"]["error"]["code"] . ", messsage = " . $result["content"]["error"]["message"]); 
    }
} else {
    ### Use existing role
    $roleId = $role_with_same_access['id'];
}


### Assigning the role to the user
$userId = Arr::get($platform,'session.user.id');
$url = $baseUrl.'system/app/?fields=id,name';
$apps = Arr::get($get($url,null, $options), 'content.resource');
$user_to_app_to_role_by_user_id = [];
foreach ($apps as $app) {
    ### Defining user_to_app_to_role_by_user_id
    $user_to_app_to_role_by_user_id [] = [
        'app_id' => $app['id'],
        'role_id' => $roleId,
        'user_id' => $userId
        ];
}

## Updating the user
$url = $baseUrl."system/user/$userId?fields=*&related=user_to_app_to_role_by_user_id";
$userData = Arr::get($get($url,null, $options), 'content');
if (count($userData['user_to_app_to_role_by_user_id']) === 0) {
    $userData['user_to_app_to_role_by_user_id'] = $user_to_app_to_role_by_user_id; 
} else {
    foreach ($userData['user_to_app_to_role_by_user_id'] as $userAppRole) {
        foreach ($user_to_app_to_role_by_user_id as &$newuserAppRole) {
            if ($userAppRole['app_id'] === $newuserAppRole['app_id']) {
                $newuserAppRole['id'] = $userAppRole['id'];
            }
        }
    }
    $userData['user_to_app_to_role_by_user_id'] = $user_to_app_to_role_by_user_id; 
}

### Assign the role to user
$result = $put($url, json_encode($userData, JSON_UNESCAPED_SLASHES), $options);

if(isset($result['status_code']) && $result['status_code'] < 400) {
    var_dump(print_r($result));
} else {
    throw new \Exception("error code = " . $result["content"]["error"]["code"] . ", messsage = " . $result["content"]["error"]["message"]); 
}







