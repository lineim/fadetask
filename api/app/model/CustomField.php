<?php
/**
 * This file is part of fade task project.
 *
 * @author    lvshuang1201@gmail.com
 * @copyright lvshuang1201@gmail.com
 * @link      https://www.fadetask.com
 */
namespace app\model;
use support\Model;

class CustomField extends Model
{
    protected $table = 'kanban_custom_field';

    public $timestamps = false;
}