<?php

namespace Plyn\Models\Example;

use Plyn\Core\Model;

/**
 * Пример модели контента Plyn
 */

class Author extends Model
{
    public function __construct()
    {
        parent::__construct();

        $this->type = 'author';

        // Описание в интерфейсе администратора
        $this->description = 'Авторы книг.';

        $this->properties = [
            // title обязателен
            [
                'name' => 'title',
                'description' => 'Имя',
                'required' => true,
                'searchable' => true,
                'type' => '\Plyn\Property\Str',
                'input' => 'text',
                'validate' => 'minlength(3)'
            ],
            [
                'name' => 'bio',
                'description' => 'Биография',
                'searchable' => true,
                'type' => '\Plyn\Property\Str',
                'input' => 'textarea'
            ],
            [
                'name' => 'email',
                'description' => 'Email',
                'searchable' => true,
                'type' => '\Plyn\Property\Str',
                'input' => 'text',
                'validate' => 'emaildomain'
            ],
            [
                'name' => 'picture',
                'description' => 'Изображение',
                'required' => true,
                'type' => '\Plyn\Property\Upload',
                'directory' => '/uploads', // Каталог относительно PATH (без завершающего слеша)
                'input' => 'upload',
                'validate' => [ ['extension', 'allowed=jpeg,jpg,gif,png'], ['size', 'size=1M'] ]
            ],
            [
                'name' => 'book',
                'description' => 'Книга',
                'type' => '\Plyn\Property\Manytomany',
                'input' => 'tomany'
            ]
        ];
    }
}
