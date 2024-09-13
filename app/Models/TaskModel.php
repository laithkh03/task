<?php

namespace App\Models;
use CodeIgniter\Model;

class TaskModel extends Model {
    protected $table = 'tasks';
    protected $allowedFields = ['title', 'description', 'status', 'due_date', 'user_id'];
}
