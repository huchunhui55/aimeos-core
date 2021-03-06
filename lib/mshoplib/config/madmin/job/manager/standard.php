<?php

/**
 * @copyright Metaways Infosystems GmbH, 2011
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015
 */

return array(
	'delete' => array(
		'ansi' => '
			DELETE FROM "madmin_job"
			WHERE :cond
			AND "siteid" = ?
		',
	),
	'insert' => array(
		'ansi' => '
			INSERT INTO "madmin_job" (
				"siteid", "label", "method", "parameter", "result", "status",
				"editor", "mtime", "ctime"
			) VALUES (
				?, ?, ?, ?, ?, ?, ?, ?, ?
			)
		',
	),
	'update' => array(
		'ansi' => '
			UPDATE "madmin_job"
			SET "siteid" = ?, "label" = ?, "method" = ?, "parameter" = ?,
				"result" = ?, "status" = ?, "editor" = ?, "mtime" = ?
			WHERE "id" = ?
		',
	),
	'search' => array(
		'ansi' => '
			SELECT majob."id" AS "job.id", majob."siteid" AS "job.siteid",
				majob."label" AS "job.label", majob."method" AS "job.method",
				majob."parameter" AS "job.parameter", majob."result" AS "job.result",
				majob."status" AS "job.status", majob."editor" AS "job.editor",
				majob."mtime" AS "job.mtime", majob."ctime" AS "job.ctime"
			FROM "madmin_job" AS majob
			:joins
			WHERE :cond
			GROUP BY majob."id", majob."siteid", majob."label",
				majob."method", majob."parameter", majob."result", majob."status",
				majob."editor", majob."mtime", majob."ctime" /*-orderby*/, :order /*orderby-*/
			/*-orderby*/ ORDER BY :order /*orderby-*/
			LIMIT :size OFFSET :start
		',
	),
	'count' => array(
		'ansi' => '
			SELECT COUNT(*) AS "count"
			FROM(
				SELECT DISTINCT majob."id"
				FROM "madmin_job" AS majob
				:joins
				WHERE :cond
				LIMIT 10000 OFFSET 0
			) AS list
		',
	),
	'newid' => array(
		'mysql' => 'SELECT LAST_INSERT_ID()',
	),
);
