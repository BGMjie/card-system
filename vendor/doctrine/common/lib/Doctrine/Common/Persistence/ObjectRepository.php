<?php
 namespace Doctrine\Common\Persistence; interface ObjectRepository { public function find($id); public function findAll(); public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null); public function findOneBy(array $criteria); public function getClassName(); } 