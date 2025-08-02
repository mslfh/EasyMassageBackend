<?php

namespace App\Contracts;

interface UserContract
{
    public function getAll();
    public function getPaginatedUsers($start, $count, $filter, $sortBy, $descending);
    public function findById($id);
    public function fetchByKeyword($field);
    public function findByField($search, $field , $fuzzy );
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function verifyCurrentPassword($currentPassword);
}
