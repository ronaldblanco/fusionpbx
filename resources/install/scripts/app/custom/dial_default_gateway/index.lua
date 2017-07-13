--connect to the database
require "resources.functions.database_handle"

local dbh = database_handle('system');

if (session:ready()) then

    local sql = "";
    local domain_id = session:getVariable("domain_uuid")

    if (domain_id == nil) then
        domain_id = session:getVariable("domain_name") 
    end
    else
        sql = "SELECT gateway_uuid FROM v_gateways "
        sql = sql .. "WHERE domain_uuid = '"..domain_id.."' "
        sql = sql .. "AND enabled = 'true' LIMIT 1"
    end

    if (domain_id ~= nil) then
        if (sql == "") then
            sql = "SELECT gateway_uuid FROM v_gateways "
            sql = sql .. "WHERE domain_uuid = "
            sql = sql .. "(SELECT domain_uuid FROM v_domains "
            sql = sql .. "WHERE domain_name = '"..domain_id.."' "
            sql = sql .. "and enabled = 'true') "
            sql = sql .. "AND enabled = 'true' LIMIT 1"
        end
        dbh:query(sql, function(row)
            gateway_uuid = row["gateway_uuid"] and row["gateway_uuid"] or nil
        end);
        if (gateway_uuid ~= nil) then
            freeswitch.consoleLog("NOTICE", "[dial_default_gateway] Dialing through gateway "..gateway_uuid.."\n");
            local callee_id_number = session:getVariable("callee_id_number")
            callee_id_number = callee_id_number and callee_id_number or "" 
            session:execute("bridge", "sofia/gateway" .. gateway_uuid .. "/" .. callee_id_number)
        else
            freeswitch.consoleLog("NOTICE", "[dial_default_gateway] Cannot get gateway for domain\n");
        end
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
