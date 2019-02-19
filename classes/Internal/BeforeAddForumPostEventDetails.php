<?php

/*
 * Bear CMS addon for Bear Framework
 * https://bearcms.com/
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

namespace BearCMS\Internal;

/**
 * @property string $categoryID
 * @property array $author
 * @property string $title
 * @property string $text
 * @property string $status
 * @property bool $cancel
 * @property string $cancelMessage
 * @internal
 */
class BeforeAddForumPostEventDetails
{

    use \IvoPetkov\DataObjectTrait;

    /**
     * 
     * @param string $categoryID
     * @param array $author
     * @param string $title
     * @param string $text
     * @param string $status
     */
    public function __construct(string $categoryID, array $author, string $title, string $text, string $status)
    {
        $this
                ->defineProperty('categoryID', [
                    'type' => 'string'
                ])
                ->defineProperty('author', [
                    'type' => 'array'
                ])
                ->defineProperty('title', [
                    'type' => 'string'
                ])
                ->defineProperty('text', [
                    'type' => 'string'
                ])
                ->defineProperty('status', [
                    'type' => 'string'
                ])
                ->defineProperty('cancel', [
                    'type' => 'bool',
                    'init' => function() {
                        return false;
                    }
                ])
                ->defineProperty('cancelMessage', [
                    'type' => 'string'
                ])
        ;
        $this->categoryID = $categoryID;
        $this->author = $author;
        $this->title = $title;
        $this->text = $text;
        $this->status = $status;
    }

}
