<?php

namespace Plyn\Models\Example;

use Plyn\Core\Model;

/**
 * Пример модели контента Plyn
 */

class Genre extends Model
{
    public function __construct()
    {
        parent::__construct();

        $this->type = 'genre';

        // Описание в интерфейсе администратора
        $this->description = 'Жанры книг.';

        $this->properties = [
            // title обязателен
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
