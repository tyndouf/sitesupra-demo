<?php

namespace Supra\FileStorage\Entity;

/**
 * Folder object
 * @Entity
 * @Table(name="folder")
 */
class Folder extends Abstraction\File
{
	/**
	 * {@inheritdoc}
	 */
	const TYPE_ID = 1;
}
