<?php

/*
 * Forums addon for Bear CMS
 * https://github.com/bearcms/forums-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

namespace BearCMS\Internal\Data\Utilities;

use BearFramework\App;

/**
 * @internal
 */
class ForumCategories
{

    /**
     * 
     * @param string $name
     * @param string $status
     * @return string
     */
    static function add(string $name, string $status): string
    {
        $app = App::get();
        $id = md5(uniqid());
        $data = [
            'id' => $id,
            'name' => $name,
            'status' => $status
        ];

        $dataKey = 'bearcms/forums/categories/category/' . md5($id) . '.json';
        $app->data->set($app->data->make($dataKey, json_encode($data)));

        return $id;
    }
}
