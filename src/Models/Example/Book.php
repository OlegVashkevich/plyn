<?php

namespace Plyn\Models\Example;

use Plyn\Core\Model;

/**
 * Пример модели контента Plyn
 */

class Book extends Model
{
    public function __construct()
    {
        parent::__construct();

        $this->type = 'book';

        // Описание в интерфейсе администратора
        $this->description = 'Книга — это один из видов печатной продукции.';

        $this->properties = [
            // title обязателен
            [
                'name' => 'title',
                'description' => 'Название',
                'required' => true,
                'searchable' => true,
                'type' => '\Plyn\Property\Str',
                'input' => 'text'
            ],
            [
                'name' => 'description',
                'description' => 'Описание',
                'searchable' => true,
                'type' => '\Plyn\Property\Str',
                'input' => 'textarea'
            ],
            [
                'name' => 'picture',
                'description' => 'Изображение',
                'required' => true,
                'type' => '\Plyn\Property\Fileselect',
                'extensions' => 'jpeg,jpg,gif,png', // Разрешенные расширения
                'directory' => '/files', // Каталог относительно PATH (без завершающего слеша)
                'input' => 'fileselect'
            ],
            [
                'name' => 'position',
                'description' => 'Порядок',
                'autovalue' => true,
                'type' => '\Plyn\Property\Position',
                'input' => 'text'
            ],
            [
                'name' => 'slug',
                'description' => 'Символьный код',
                'autovalue' => true,
                'type' => '\Plyn\Property\Slug',
                'input' => 'text'
            ],
            [
                'name' => 'author',
                'description' => 'Автор',
                'required' => true,
                'type' => '\Plyn\Property\Manytomany',
                'input' => 'tomany'
            ],
            [
                'name' => 'genre',
                'description' => 'Жанры',
                'required' => true,
                'type' => '\Plyn\Property\Manytoone',
                'input' => 'manytoone'
            ]
        ];
    }
}
