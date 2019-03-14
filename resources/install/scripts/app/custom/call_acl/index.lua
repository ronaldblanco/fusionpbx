-- Check both clid and destination number against ACL

-- connect to the database
require "resources.functions.database_handle"

local dbh = database_handle('system')

local function log(message)
    freeswitch.consoleLog("NOTICE", "[call_acl] "..message.."\n");
end

local function convert_pattern(pattern)
    
    -- Cleanup pattern-related magical characters
    converted_pattern = pattern:gsub("%(", "%%(")
    converted_pattern = converted_pattern:gsub("%)", "%%)")
    converted_pattern = converted_pattern:gsub("%%", "%%%%")
    converted_pattern = converted_pattern:gsub("%.", "%%.")
    converted_pattern = converted_pattern:gsub("%[", "%%[")
    converted_pattern = converted_pattern:gsub("%]", "%%]")
    converted_pattern = converted_pattern:gsub("%+", "%%+")
    converted_pattern = converted_pattern:gsub("%-", "%%-")
    converted_pattern = converted_pattern:gsub("%?", "%%?")

    -- Internal convention X - any digit, * - any number of digits
    converted_pattern = converted_pattern:gsub("X", "%d")
    converted_pattern = converted_pattern:gsub("%*", ".*")

    return converted_pattern

end

if (session:ready()) then

    log("Process started...")

    local sql = ""

    local source = session:getVariable("caller_id_number")
    local destination = session:getVariable("destination_number")

    if (source == nil or destination == nil) then
        log("Cannot get callerid or destination number")
        return
    end


    if (domain_id == nil) then
        domain_id = session:getVariable("domain_name") or session:getVariable("sip_invite_domain")
    else
        sql = "SELECT call_acl_name, "
        sql = sql .. "call_acl_source, "
        sql = sql .. "call_acl_destination, "
        sql = sql .. "call_acl_action "
        sql = sql .. "FROM v_call_acl "
        sql = sql .. "WHERE domain_uuid = '" .. domain_id .. "' "
        sql = sql .. "AND call_acl_enabled = 'true' "
        sql = sql .. "ORDER BY call_acl_order"
    end

    if (domain_id ~= nil) then
        if (sql == "") then
            sql = "SELECT call_acl_name, "
            sql = sql .. "call_acl_source, "
            sql = sql .. "call_acl_destination, "
            sql = sql .. "call_acl_action "
            sql = sql .. "FROM v_call_acl "
            sql = sql .. "WHERE domain_uuid ="
            sql = sql .. " (SELECT domain_uuid FROM v_domains"
            sql = sql .. " WHERE domain_name = '" .. domain_id .. "'"
            sql = sql .. " AND domain_enabled = 'true') "
            sql = sql .. "AND call_acl_enabled = 'true' "
            sql = sql .. "ORDER BY call_acl_order"
        end
        dbh:query(sql, function(row)
            call_acl_name = row['call_acl_name']
            call_acl_source = row['call_acl_source']
            call_acl_destination = row['call_acl_destination']
            call_acl_action = row['call_acl_action']

            log("Processing ACL " .. call_acl_name)

            call_acl_source = convert_pattern(call_acl_source)
            call_acl_destination = convert_pattern(call_acl_destination)

            if (source:find(call_acl_source) or destination:find(call_acl_destination)) then
                log("ACL " .. call_acl_name .. " matched")
                if call_acl_action == 'reject' then
                    log("ACL is reject. Stop process call")
                    session:execute('hangup', "BEARERCAPABILITY_NOTAUTH")
                end
                log("ACL is allow. Stop process ACLs")
                -- We found pattern match and this is allow
                return
            end

        end);
        dbh:release()
    end

    log("ACL processing end. Contunue call")
end