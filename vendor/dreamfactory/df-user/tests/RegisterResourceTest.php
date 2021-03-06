<?php
use DreamFactory\Core\Enums\Verbs;
use DreamFactory\Core\Utility\Session;
use DreamFactory\Core\Models\User;
use Illuminate\Support\Arr;

class RegisterResourceTest extends \DreamFactory\Core\Testing\TestCase
{
    const RESOURCE = 'register';

    protected $serviceId = 'user';

    protected $user1 = [
        'name'              => 'John Doe',
        'first_name'        => 'John',
        'last_name'         => 'Doe',
        'email'             => 'jdoe@dreamfactory.com',
        'password'          => 'test12345678',
        'security_question' => 'Make of your first car?',
        'security_answer'   => 'mazda',
        'is_active'         => true
    ];

    public function tearDown()
    {
        $email = Arr::get($this->user1, 'email');
        User::whereEmail($email)->delete();

        // Restoring user service
//        $data = [
//            'allow_open_registration' => 0,
//            'open_reg_role_id' => null,
//            'open_reg_email_service_id' => 6,
//            'open_reg_email_template_id' => 2,
//            'invite_email_service_id' => 6,
//            'invite_email_template_id' => 1
//        ];
//        \DreamFactory\Core\User\Models\UserConfig::whereServiceId(7)->update($data);

        parent::tearDown();
    }

    public function setUp()
    {
        parent::setUp();
        \Illuminate\Database\Eloquent\Model::unguard(false);

        // Enable open registration
        $data = [
            'allow_open_registration' => 1,
            'open_reg_role_id' => null,
            'open_reg_email_service_id' => null,
            'open_reg_email_template_id' => null,
            'invite_email_service_id' => null,
            'invite_email_template_id' => null
        ];
        \DreamFactory\Core\User\Models\UserConfig::whereServiceId(7)->update($data);
    }

    public function testPOSTRegister()
    {
        $u = $this->user1;
        $password = Arr::get($u, 'password');
        $payload = [
            'first_name'            => Arr::get($u, 'first_name'),
            'last_name'             => Arr::get($u, 'last_name'),
            'name'                  => Arr::get($u, 'name'),
            'email'                 => Arr::get($u, 'email'),
            'phone'                 => Arr::get($u, 'phone'),
            'security_question'     => Arr::get($u, 'security_question'),
            'security_answer'       => Arr::get($u, 'security_answer'),
            'password'              => $password,
            'password_confirmation' => Arr::get($u, 'password_confirmation', $password)
        ];

        Session::setUserInfoWithJWT(User::find(1));
        $r = $this->makeRequest(Verbs::POST, static::RESOURCE, [], $payload);
        $c = $r->getContent();
        $this->assertTrue(Arr::get($c, 'success'));

        Session::put('role.name', 'test');
        Session::put('role.id', 1);

        $this->service = ServiceManager::getService('user');
        $r = $this->makeRequest(
            Verbs::POST,
            'session',
            [],
            ['email' => Arr::get($u, 'email'), 'password' => Arr::get($u, 'password')]
        );
        $c = $r->getContent();

        $this->assertTrue(!empty(Arr::get($c, 'session_id')));
    }
}