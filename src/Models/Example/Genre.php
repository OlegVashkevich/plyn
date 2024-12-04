<?php

namespace Plyn\Models\Example;

use Plyn\Core\Model;

/**
 * Example Plyn content model
 */

class Genre extends Model
{
    public function __construct()
    {
        parent::__construct();

        $this->type = 'genre';

        // Description in admin interface
        $this->description = 'Жанры книг.';

        $this->properties = [
            // Allways have a title
            [
                'name' => 'title',
                'description' => 'Название',
                'required' => true,
                'type' => '\Plyn\Property\Str',
                'input' => 'text'
            ],
            [
                'name' => 'description',
                'description' => 'Описание',
                'type' => '\Plyn\Property\Str',
                'input' => 'textarea'
            ],
            [
                'name' => 'book',
                'description' => 'Книги',
                'type' => '\Plyn\Property\Onetomany',
                'input' => 'tomany'
            ]
        ];
    }
}
