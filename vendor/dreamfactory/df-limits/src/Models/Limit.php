<?php

namespace DreamFactory\Core\Limit\Models;

use DreamFactory\Core\Models\BaseSystemModel;
use DreamFactory\Core\Enums\DateTimeIntervals;

class Limit extends BaseSystemModel
{
    protected $table = 'limits';

    public static $limitTypes = [
        'instance'                            => 'instance',
        'instance.user'                       => 'instance.user:%s',
        'instance.each_user'                  => 'instance.each_user:%s',
        'instance.user.service'               => 'instance.user:%s.service:%s',
        'instance.each_user.service'          => 'instance.each_user:%s.service:%s',
        'instance.service'                    => 'instance.service:%s',
        'instance.service.endpoint'           => 'instance.service:%s.endpoint:%s',
        'instance.user.service.endpoint'      => 'instance.user:%s.service:%s.endpoint:%s',
        'instance.each_user.service.endpoint' => 'instance.each_user:%s.service:%s.endpoint:%s',
        'instance.role'                       => 'instance.role:%s',
    ];

    public static $eachUserTypes = [
        'instance.each_user',
        'instance.each_user.service',
        'instance.each_user.service.endpoint'
    ];

    public static $limitPeriods = [
        'minute',
        'hour',
        'day',
        '7-day',
        '30-day',
    ];

    protected $rules = [
        'type'   => 'required',
        'rate'   => 'required',
        'period' => 'required',
        'name'   => 'required'
    ];

    public static $limitIntervals = [
        DateTimeIntervals::SECONDS_PER_MINUTE,
        DateTimeIntervals::SECONDS_PER_HOUR,
        DateTimeIntervals::SECONDS_PER_DAY,
        DateTimeIntervals::SECONDS_PER_WEEK,
        DateTimeIntervals::SECONDS_PER_MONTH,
    ];

    protected $hidden = [
        'create_date',
    ];

    protected $fillable = [
        'type',
        'rate',
        'user_id',
        'role_id',
        'service_id',
        'endpoint',
        'verb',
        'name',
        'description',
        'period',
        'key_text',
        'is_active',
    ];

    protected $casts = [
        'rate'       => 'integer',
        'period'     => 'integer',
        'verb'       => 'string',
        'is_active'  => 'boolean',
        'user_id'    => 'integer',
        'role_id'    => 'integer',
        'service_id' => 'integer',
    ];

    /**
     * Resolves and builds unique key based on limit type.
     *
     * @param $limitType
     * @param $userId
     * @param $roleId
     * @param $serviceId
     * @param $endpoint
     * @param $verb
     * @param $limitPeriod
     *
     * @return string
     */
    public function resolveCheckKey($limitType, $userId, $roleId, $serviceId, $endpoint, $verb, $limitPeriod)
    {
        if (isset(self::$limitTypes[$limitType])) {

            switch ($limitType) {
                case 'instance':
                    $key = static::$limitTypes[$limitType];
                    break;

                case 'instance.user':
                case 'instance.each_user':
                    $key = sprintf(static::$limitTypes[$limitType], $userId);
                    break;

                case 'instance.role':
                    $key = sprintf(static::$limitTypes[$limitType], $roleId);
                    break;

                case 'instance.user.service':
                case 'instance.each_user.service':
                    $key = sprintf(static::$limitTypes[$limitType], $userId, $serviceId);
                    break;

                case 'instance.service':
                    $key = sprintf(static::$limitTypes[$limitType], $serviceId);
                    break;

                case 'instance.service.endpoint':

                    $typeStr = static::$limitTypes[$limitType];
                    $key = sprintf($typeStr, $serviceId, $endpoint);
                    break;

                case 'instance.user.service.endpoint':

                    $typeStr = static::$limitTypes[$limitType];
                    $key = sprintf(static::$limitTypes[$limitType], $userId, $serviceId, $endpoint);
                    break;

                case 'instance.each_user.service.endpoint':
                    $key = sprintf(static::$limitTypes[$limitType], $userId, $serviceId, $endpoint);
                    break;
            }

            /** Finally add the verb and the period to the string */
            if (!is_null($verb)) {
                /** if a valid verb is passed, concat it on. */
                $key .= sprintf('.verb:%s', $verb);
            }

            return $key . '.' . static::$limitPeriods[$limitPeriod];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getRules()
    {
        $rules = parent::getRules();
        $rules['period'] .= '|in:' . implode(',', range(0, (count(static::$limitPeriods) - 1)));
        $rules['type'] .= '|in:' . implode(',', array_keys(static::$limitTypes));

        return $rules;
    }

    public function user_by_user_id()
    {
        return $this->belongsTo('DreamFactory\Core\Models\User', 'user_id');
    }

    public function service_by_service_id()
    {
        return $this->belongsTo('DreamFactory\Core\Models\Service', 'service_id');
    }

    public function role_by_role_id()
    {
        return $this->belongsTo('DreamFactory\Core\Models\Role', 'role_id');
    }
}