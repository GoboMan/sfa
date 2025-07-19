-------------------------------------------------------------------------------
--	取引先テーブルに営業テーブルを結合して一覧取得
-------------------------------------------------------------------------------
@get_entity_rows_with_user_name
select
	et.*,
	ut.name as user_name
from
	entity as et
left join
	user as ut
on
	ut.user_id = et.user_id
where
	et.deleted = 0

-------------------------------------------------------------------------------
--	user_id指定で、取引先テーブルに営業テーブルを結合して一覧取得
-------------------------------------------------------------------------------
@get_entity_row_with_user_name_by_id
select
	et.*,
	ut.name as user_name
from
	entity as et
left join
	user as ut
on
	ut.user_id = et.user_id
where
	et.entity_id = %s and
	ut.user_id = %s and
	et.deleted = 0