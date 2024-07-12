<?php

namespace AmphiBee\Eloquent;

/**
 * Connection Resolver
 *
 * @package AmphiBee\Eloquent
 * @author AmphiBee <hello@amphibee.fr>
 * @author Thomas Georgel <thomas@hydrat.agency>
 */
class WpdbPdo
{
    /**
     * DB Instance
     */
    protected $db;

    public function __construct($wpdb)
    {
        $this->db = $wpdb;
    }

    public function lastInsertId()
    {
        return $this->db->insert_id;
    }

    public function prefix(?string $tableName = null)
    {
        if (!$tableName) {
            return $this->db->prefix;
        }

        return !in_array($tableName, $this->db->global_tables) ? $this->db->prefix : $this->db->base_prefix;
    }
}
