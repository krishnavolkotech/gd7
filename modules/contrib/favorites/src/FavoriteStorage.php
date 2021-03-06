<?php

/**
 * @file
 * Contains \Drupal\favorites\FavoriteStorage.
 */

namespace Drupal\favorites;

class FavoriteStorage {

    /**
     * {@inheritdoc}
     */
    static function delete($fid) {
      \Drupal::database()->delete('favorites')
                ->condition('fid', $fid)
                ->execute();
    }

    /**
     * {@inheritdoc}
     */
    static function getFavorites($uid) {
        $result = \Drupal::database()->query('select * from {favorites} where uid = :uip order by timestamp DESC', array(':uip' => $uid));
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    static function getFav($fid) {
        return \Drupal::database()->query('select * from {favorites} where fid=:fid', array(':fid' => $fid))->fetchObject();
    }

    /**
     * {@inheritdoc}
     */
    static function deleteFavorite($fid) {
        \Drupal::database()->delete('favorites')
                ->condition('fid', $fid)
                ->execute();
    }

    /**
     * {@inheritdoc}
     */
    static function deleteFav($uid, $path, $query) {
        \Drupal::database()->delete('favorites')
                ->condition('uid', $uid)
                ->condition('path', $path)
                ->condition('query', $query)
                ->execute();
    }

    /**
     * {@inheritdoc}
     */
    static function addFav($uid, $path, $query, $title) {
        \Drupal::database()->insert('favorites')
                ->fields(array(
                    'uid' => $uid,
                    'path' => $path,
                    'query' => $query,
                    'title' => $title,
                    'timestamp' => REQUEST_TIME,
                ))
                ->execute();
    }

    /**
     * {@inheritdoc}
     */
    static function favExists($uid, $path, $query) {
        $fidquery = \Drupal::database()->select('favorites', 'fav');
        $fidquery->fields('fav', array('fid'))
                ->condition('uid', $uid, '=')
                ->condition('path', $path, '=');
        if ($query) {
            $fidquery->condition('query', $query, '=');
        }
        $fid = $fidquery->execute()->fetchAssoc();
        if (isset($fid) && !empty($fid['fid'])) {
            return $fid['fid'];
        } else {
            return FALSE;
        }
    }

}
