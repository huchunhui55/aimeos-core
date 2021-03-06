<?php

/**
 * @copyright Metaways Infosystems GmbH, 2011
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015
 */

return array(
	'delete' => array(
		'ansi' => '
			DELETE FROM "mshop_text_type"
			WHERE :cond AND siteid = ?
		'
	),
	'insert' => array(
		'ansi' => '
			INSERT INTO "mshop_text_type" (
				"siteid", "code", "domain", "label", "status", "mtime",
				"editor", "ctime"
			) VALUES (
				?, ?, ?, ?, ?, ?, ?, ?
			)
		'
	),
	'update' => array(
		'ansi' => '
			UPDATE "mshop_text_type"
			SET "siteid"=?, "code"=?, "domain" = ?, "label" = ?, "status" = ?,
				"mtime" = ?, "editor" = ?
			WHERE "id" = ?
		'
	),
	'search' => array(
		'ansi' => '
			SELECT mtexty."id" AS "text.type.id", mtexty."siteid" AS "text.type.siteid",
				mtexty."code" AS "text.type.code", mtexty."domain" AS "text.type.domain",
				mtexty."label" AS "text.type.label", mtexty."status" AS "text.type.status",
				mtexty."mtime" AS "text.type.mtime", mtexty."editor" AS "text.type.editor",
				mtexty."ctime" AS "text.type.ctime"
			FROM "mshop_text_type" mtexty
			:joins
			WHERE :cond
			GROUP BY mtexty."id", mtexty."siteid", mtexty."code", mtexty."domain",
				mtexty."label", mtexty."status", mtexty."mtime", mtexty."editor",
				mtexty."ctime" /*-orderby*/, :order /*orderby-*/
			/*-orderby*/ ORDER BY :order /*orderby-*/
			LIMIT :size OFFSET :start
		'
	),
	'count' => array(
		'ansi' => '
			SELECT COUNT(*) AS "count"
			FROM (
				SELECT DISTINCT mtexty."id"
				FROM "mshop_text_type" mtexty
				:joins
				WHERE :cond
				LIMIT 10000 OFFSET 0
			) AS list
		'
	),
	'newid' => array(
		'mysql' => 'SELECT LAST_INSERT_ID()'
	),
);

