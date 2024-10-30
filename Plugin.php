<?php
/**
 * Copyright (c) 2012 Oliver Seidel (email : oliver.seidel @ deliciousdays.com)
 * Copyright (c) 2017 Bastian Germann
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Plugin Name: Old Tracking DB for cformsII
 * Plugin URI: https://wordpress.org/plugins/cforms2-old-tracking-db/
 * Description: This enables a compatibility layer for the old cformsII Tracking DB.
 * Author: Bastian Germann
 * Version: 0.3
 * Requires at least: 5.2
 * Requires Plugins: cforms2
 */
require_once plugin_dir_path(__FILE__) . 'OldTrackingDB.php';

add_action('init', new Cforms2\OldTrackingDB());

if (!function_exists('get_cforms_entries')) {

    /**
     * API function: get_cforms_entries
     *
     * This function allows to conveniently retrieve submitted data from the cformsII tracking tables.
     *
     * @param string $fname   text string (regexp pattern), e.g. the form name
     * @param string $from    DATETIME string (format: Y-m-d H:i:s). Date & time defining the target period, e.g. 2008-09-17 15:00:00
     * @param string $to      DATETIME string (format: Y-m-d H:i:s). Date & time defining the target period, e.g. 2008-09-17 15:00:00
     * @param string $sort    'form', 'id', 'date', 'ip', 'email' or any form input field, e.g. 'Your Name'
     * @param int    $limit   limiting the number of results, '' (empty or false) = no limits!
     * @param string $sortdir "asc" for ascending or "desc" for descending sort direction
     *
     * @return array a set of stored form submissions in a multi-dimensional array
     *
     * Examples:
     * get_cforms_entries() => all data, no filters
     * get_cforms_entries('contact',false,false,'date',5,'desc') => last 5 submissions of "my contact form", order by date
     * get_cforms_entries(false,date ("Y-m-d H:i:s", time()-(3600*2))) => all submissions in the last 2 hours
     */
    function get_cforms_entries($fname = false, $from = false, $to = false, $sort = false, $limit = false, $sortdir = 'asc', $limitstart = 0)
    {
        return Cforms2\OldTrackingDB::getEntries($fname, $from, $to, $sort, $limit, $sortdir, $limitstart);
    }
}
