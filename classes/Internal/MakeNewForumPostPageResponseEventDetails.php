<?php

/*
 * Bear CMS addon for Bear Framework
 * https://bearcms.com/
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

namespace BearCMS\Internal;

/**
 * @property \BearFramework\App\Response $response
 * @property string $forumCategoryID
 * @internal
 */
class MakeNewForumPostPageResponseEventDetails
{

    use \IvoPetkov\DataObjectTrait;

    /**
     * 
     * @param \BearFramework\App\Response $response
     * @param string $forumCategoryID
     */
    public function __construct(\BearFramework\App\Response $response, string $forumCategoryID)
    {
        $this
                ->defineProperty('response', [
                    'type' => \BearFramework\App\Response::class
                ])
                ->defineProperty('forumCategoryID', [
                    'type' => 'string'
                ])
        ;
        $this->response = $response;
        $this->forumCategoryID = $forumCategoryID;
    }

}
