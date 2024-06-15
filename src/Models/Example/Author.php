<?php

namespace Plyn\Models\Example;

/**
 * Example Plyn content model
 */

class Author extends \Plyn\Core\Model {

	function __construct() {

		parent::__construct();
		
		$this->type = 'author';
		
		// Description in admin interface
		$this->description = 'Авторы книг.';

		$this->properties = [
			// Allways have a title
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
				'directory' => '/uploads', // Directory relative to PATH (no trailing slash)
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

?>