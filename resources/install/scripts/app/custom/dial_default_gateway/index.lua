--connect to the database
require "resources.functions.database_handle"
--for loop through arguments
for key,value in pairs(argv) do
    --if (key >= 0) then
        freeswitch.consoleLog("notice", "[dial_default_gateway.app] argv["..key.."]: "..value.."\n");
    --end
end

local dbh = database_handle('system');

if (session:ready()) then

    local domain_id = session:getVariable("domain_uuid")

    if (domain_id == nil) then
        domain_id = session:getVariable("domain_name")
    end 

    if (domain_id ~= nil) then
        -- 
        -- session:setVariable("default_gateway", default_gateway)
    else
        freeswitch.consoleLog("NOTICE", "[dial_default_gateway] Cannot get domain_uuid or domain_id\n");
        session:execute('info')
    end
end

-- sql = "SELECT * FROM v_ring_groups ";
-- sql = sql .. "where ring_group_uuid = '"..ring_group_uuid.."' ";
-- status = dbh:query(sql, function(row)
--         domain_uuid = row["domain_uuid"];
--         ring_group_name = row["ring_group_name"];
--         ring_group_extension = row["ring_group_extension"];
--         ring_group_forward_enabled = row["ring_group_forward_enabled"];
--         ring_group_forward_destination = row["ring_group_forward_destination"];
--         ring_group_cid_name_prefix = row["ring_group_cid_name_prefix"];
--         ring_group_cid_number_prefix = row["ring_group_cid_number_prefix"];
--         missed_call_app = row["ring_group_missed_call_app"];
--         missed_call_data = row["ring_group_missed_call_data"];
-- end);
