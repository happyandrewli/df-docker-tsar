<?php

namespace DreamFactory\Core\GraphQL\Http\Controllers;

use Illuminate\Http\Request;

class GraphQLController extends Controller
{
    public function query(Request $request)
    {
        $isBatch = !$request->has('query');
        $inputs = $request->all();

        if (!$isBatch) {
            $data = $this->executeQuery($inputs);
        } else {
            $data = [];
            foreach ($inputs as $input) {
                $data[] = $this->executeQuery($input);
            }
        }

        $headers = config('graphql.headers', []);
        $options = config('graphql.json_encoding_options', 0);

        $errors = !$isBatch ? array_get($data, 'errors', []) : [];
        $authorized = array_reduce($errors, function ($authorized, $error) {
            return !$authorized || array_get($error, 'message') === 'Unauthorized' ? false : true;
        }, true);
        if (!$authorized) {
            return response()->json($data, 403, $headers, $options);
        }

        return response()->json($data, 200, $headers, $options);
    }

    protected function executeQuery($input)
    {
        $variablesInputName = config('graphql.variables_input_name', 'variables');
        $query = array_get($input, 'query');
        $variables = array_get($input, $variablesInputName);
        if (is_string($variables)) {
            $variables = json_decode($variables, true);
        }
        $operationName = array_get($input, 'operationName');
        $context = $this->queryContext($query, $variables);
        $root = null;

        return app('graphql')->query($query, $variables, [
            'context'       => $context,
            'operationName' => $operationName
        ]);
    }

    protected function queryContext($query, $variables)
    {
        try {
            return app('auth')->user();
        } catch (\Exception $e) {
            return null;
        }
    }
}
