<?php
/**
 * Migration: Add performance indexes to frequently-queried columns.
 */
return function (PDO $db) {
    if (function_exists('addPerformanceIndexes')) {
        addPerformanceIndexes($db);
    }
};
