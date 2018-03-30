<?php 


class TodoORM {

    public function get_todo($app,$id){
        $qb = $app['db']->createQueryBuilder();
        $qb->select('*')
        ->from('todos')
        ->where(
            $qb->expr()->eq('id', $id)
            );
        $results = $qb->execute()->fetchAll();
        //  'name = ?'
        // $queryBuilder->setParameter($placeholder, $value) instead:
        return $results[0];
    }

    public function get_todos($app, $user_id){
        $qb = $app['db']->createQueryBuilder();
        $qb->select('*')
        ->from('todos')
        ->where(
            $qb->expr()->eq('user_id', $user_id)
            );
        $results = $qb->execute()->fetchAll();
        return $results;
    }

    public function add_todo($app, $description, $user_id){
        $qb = $app['db']->createQueryBuilder();
        $qb->insert('todos')
        ->values(array(
            'user_id' => $user_id,
            'description' => "'" .$description."'"
        ));
        $app['db']->executeUpdate($qb->getSQL());
    }

    public function delete_todo($app, $id){
        $qb = $app['db']->createQueryBuilder();
        $qb->delete('todos')
        ->where(
            $qb->expr()->eq('id', $id)
            );
        $app['db']->executeUpdate($qb->getSQL());
    }

}