<?php

namespace Supra\NestedSet\Exception;

/**
 * Error on argument not inside the domain
 */
class Domain extends \DomainException implements NestedSetException
{}