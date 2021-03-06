<?php

/**
 * @copyright Metaways Infosystems GmbH, 2013
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015
 */

return array(
	'delete' => array(
		'ansi' => '
			DELETE FROM "mshop_coupon_code"
			WHERE :cond AND siteid = ?
		'
	),
	'insert' => array(
		'ansi' => '
			INSERT INTO "mshop_coupon_code" (
				"siteid", "parentid", "code", "count", "start", "end", "mtime",
				"editor", "ctime"
			) VALUES (
				?, ?, ?, ?, ?, ?, ?, ?, ?
			)
		'
	),
	'update' => array(
		'ansi' => '
			UPDATE "mshop_coupon_code"
			SET "siteid" = ?, "parentid" = ?, "code" = ?, "count" = ?,
				"start" = ?, "end" = ?, "mtime" = ?, "editor" = ?
			WHERE "id" = ?
		'
	),
	'search' => array(
		'ansi' => '
			SELECT mcouco."id" AS "coupon.code.id", mcouco."parentid" AS "coupon.code.parentid",
				mcouco."siteid" AS "coupon.code.siteid", mcouco."code" AS "coupon.code.code",
				mcouco."start" AS "coupon.code.datestart", mcouco."end" AS "coupon.code.dateend",
				mcouco."count" AS "coupon.code.count", mcouco."mtime" AS "coupon.code.mtime",
				mcouco."editor" AS "coupon.code.editor", mcouco."ctime" AS "coupon.code.ctime"
			FROM "mshop_coupon_code" AS mcouco
			:joins
			WHERE :cond
			GROUP BY mcouco."id", mcouco."parentid", mcouco."siteid", mcouco."code",
				mcouco."start", mcouco."end", mcouco."count", mcouco."mtime",
				mcouco."editor", mcouco."ctime" /*-orderby*/, :order /*orderby-*/
			/*-orderby*/ ORDER BY :order /*orderby-*/
			LIMIT :size OFFSET :start
		'
	),
	'count' => array(
		'ansi' => '
			SELECT COUNT(*) AS "count"
			FROM (
				SELECT DISTINCT mcouco."id"
				FROM "mshop_coupon_code" AS mcouco
				:joins
				WHERE :cond
				LIMIT 10000 OFFSET 0
			) AS list
		'
	),
	'counter' => array(
		'ansi' => '
			UPDATE "mshop_coupon_code"
			SET	"count" = "count" + ?, "mtime" = ?, "editor" = ?
			WHERE :cond AND "code" = ?
		'
	),
	'newid' => array(
		'mysql' => 'SELECT LAST_INSERT_ID()'
	),
);
