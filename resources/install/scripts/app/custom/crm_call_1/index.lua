-- This function is called like app_custom.lua vtiger_connector
-- You MUST specify VTiger URL in Default (or Domain) settings.
-- Also it uses freeswitch curl command, so it also need to be loaded

-- Vars to specify
-- url
-- api_key

local type_transfer_table = {
    ["owner"] = "506",
    ["tenant"] = "503",
    ["maintenance_vendor"] = "507",
    ["other"] = "512",
    ["tenant_lead"] = "502",
    ["investor_lead"] = "505",
    ["agent"] = "511",
    ["referral_source"] = "504",
    ["retail_brokerage_client"] = "513",
    ["default"] = "501"
}

local main_number = "500"

local log = require "resources.functions.log".crm_call_1

function contact_api_call(url, data, is_return)

    local cmd_string = "curl " .. url .. "?phone_number=" .. data .. " connect-timeout 1 timeout 2 post"

    if is_return == nil then 
        cmd_string = "bgapi " .. cmd_string
    end

    return api:executeString(cmd_string)
end

function podio_api_call(url, data)

    local cmd_string = "bgapi curl " .. url .. "content-type application/json connect-timeout 1 timeout 2 post '".. api_data .."'"

    api:executeString(cmd_string)
end

-- Base64 encoding/decoding
-- encoding
function base64_enc(data)
    local b='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/'

    return ((data:gsub('.', function(x) 
        local r,b='',x:byte()
        for i=8,1,-1 do r=r..(b%2^i-b%2^(i-1)>0 and '1' or '0') end
        return r;
    end)..'0000'):gsub('%d%d%d?%d?%d?%d?', function(x)
        if (#x < 6) then return '' end
        local c=0
        for i=1,6 do c=c+(x:sub(i,i)=='1' and 2^(6-i) or 0) end
        return b:sub(c+1,c+1)
    end)..({ '', '==', '=' })[#data%3+1])
end

function base64_dec(data)

    local b='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/'

    local data = string.gsub(data, '[^'..b..'=]', '')
    return (data:gsub('.', function(x)
        if (x == '=') then return '' end
        local r,f='',(b:find(x)-1)
        for i=6,1,-1 do r=r..(f%2^i-f%2^(i-1)>0 and '1' or '0') end
        return r;
    end):gsub('%d%d%d?%d?%d?%d?%d?%d?', function(x)
        if (#x ~= 8) then return '' end
        local c=0
        for i=1,8 do c=c+(x:sub(i,i)=='1' and 2^(8-i) or 0) end
        return string.char(c)
    end))
end


function process_json_answer(responce)

    local json = freeswitch.JSON()
    local data = json:decode(responce)

    if (data == nil or data[1] == nil) then
        log.warning("Cannot process " .. responce .. " as json")
        return nil
    end

    for _, v in ipairs(data) do
        if (v["route_call_to_extension"] and string.len(v["route_call_to_extension"]) > 0 and v["route_call_to_extension"] ~= 'null') then
            return v["route_call_to_extension"], v['first_name'], v['last_name']
        end
    end

    for _, v in ipairs(data) do
        if (v["route_call_option"] and string.len(v["route_call_option"]) > 0 and v["route_call_option"] ~= 'null') then
            return "option_" .. v["route_call_option"], v['first_name'], v['last_name']
        end
    end

    for _, v in ipairs(data) do
        if (v['type'] and string.len(v['type']) > 0 and v['type'] ~= 'null') then
            local data_type = string.lower(v['type'])
            data_type = data_type:gsub(" ", "_")

            return type_transfer_table[data_type], v['first_name'], v['last_name']
        end
    end

    return nil, nil, nil
end

function get_contact_uuid()

    local sql = "SELECT contact_uuid FROM v_contacts"
    sql = sql .. " WHERE domain_uuid = :domain_uuid"
    sql = sql .. " AND contact_type='customer'"
    sql = sql .. " AND contact_organization='RT'"
    sql = sql .. " LIMIT 1"

    local params= {
        domain_uuid = domain_uuid
    }

    local contact_uuid

    db:query(sql, params, function(row)
        contact_uuid = row.contact_uuid
    end);

    if contact_uuid then
        return contact_uuid
    end

    log.warning("No such Contact! Creating one for you....")

    contact_uuid = api:executeString("create_uuid")

    sql = "INSERT INTO v_contacts ("
    sql = sql .. "contact_uuid,"
    sql = sql .. "domain_uuid,"
    sql = sql .. "contact_type,"
    sql = sql .. "contact_organization,"
    sql = sql .. "last_mod_user"
    sql = sql .. ") VALUES ("
    sql = sql .. ":contact_uuid,"
    sql = sql .. ":domain_uuid,"
    sql = sql .. "'customer',"
    sql = sql .. "'RT',"
    sql = sql .. "'admin')"


    local params= {
        domain_uuid = domain_uuid,
        contact_uuid = contact_uuid
    }

    db:query(sql, params)

    return contact_uuid
end

function get_from_db(caller_id_number)

    local contact_uuid = get_contact_uuid()

    local sql = "SELECT phone_extension FROM v_contact_phones "
    sql = sql .. "WHERE phone_number = :caller_id_number"
    sql = sql .. " AND domain_uuid = :domain_uuid"

    local params = {
        domain_uuid = domain_uuid,
        caller_id_number = caller_id_number
    }

    local phone_number

    db:query(sql, params, function(row)
        phone_number = row.phone_number
    end)

    return phone_number
end

function update_or_save(caller_id_number, transfer_extension)

    local contact_uuid = get_contact_uuid()

    local sql = "SELECT contact_phone_uuid FROM v_contact_phones "
    sql = sql .. "WHERE contact_uuid = :contact_uuid"
    sql = sql .. " AND domain_uuid = :domain_uuid"
    sql = sql .. " AND phone_number = :caller_id_number"

    local contact_phone_uuid

    local params = {
        domain_uuid = domain_uuid,
        contact_uuid = contact_uuid,
        caller_id_number = caller_id_number
    }

    db:query(sql, params, function(row)
        contact_phone_uuid = row.contact_phone_uuid
    end)

    if contact_phone_uuid then
        log.info("Updating info for " .. caller_id_number .. " with " .. transfer_extension)
        sql = "UPDATE v_contact_phones SET"
        sql = sql .. " phone_extension = :transfer_extension,"
        sql = sql .. " phone_description = :transfer_extension "
        sql = sql .. "WHERE"
        sql = sql .. " contact_phone_uuid = :contact_phone_uuid"

        local params = {
            contact_phone_uuid = contact_phone_uuid,
            transfer_extension = transfer_extension
        }

        db:query(sql, params)
        return
    end

    log.info("Inserting info for " .. caller_id_number .. " with " .. transfer_extension)

    sql = "INSERT INTO v_contact_phones ("
    sql = sql .. "contact_phone_uuid,"
    sql = sql .. "domain_uuid,"
    sql = sql .. "contact_uuid,"
    sql = sql .. "phone_type_voice,"
    sql = sql .. "phone_label,"
    sql = sql .. "phone_primary,"
    sql = sql .. "phone_number,"
    sql = sql .. "phone_extension,"
    sql = sql .. "phone_description"
    sql = sql .. ") VALUES ("
    sql = sql .. ":contact_phone_uuid,"
    sql = sql .. ":domain_uuid,"
    sql = sql .. ":contact_uuid,"
    sql = sql .. "1,"
    sql = sql .. "'Work',"
    sql = sql .. "1,"
    sql = sql .. ":caller_id_number,"
    sql = sql .. ":transfer_extension,"
    sql = sql .. ":transfer_extension)"

    local params = {
        contact_phone_uuid = api:executeString("create_uuid"),
        domain_uuid = domain_uuid,
        contact_uuid = contact_uuid,
        caller_id_number = caller_id_number,
        transfer_extension = transfer_extension
    }

    db:query(sql, params)

end

function get_channel_data(call_state)

    local uuid_dump = api:executeString('uuid_dump ' .. session:get_uuid())

    local var_table = {}
    var_table['call_state'] = call_state

    for line in string.gmatch(uuid_dump, "([^\n]+)") do
        local k, v = string.match(line, "^(.*):(.*)$", 1)
        var_table[k] = v
    end

    local json = freeswitch.JSON()

    return json:encode(var_table)

end

api = freeswitch.API()

local Settings = require "resources.functions.lazy_settings"
local Database = require "resources.functions.database"

db = Database.new('system')

assert(db:connected())

if (session:ready()) then

    local call_state = argv[2]

    if (call_state and call_state == 'call_answer') then
        local crm_end_settings_url = session:getVariable('crm_end_settings_url')
        if (crm_end_settings_url) then
            crm_end_settings_url = base64_dec(crm_end_settings_url)
            local channel_var_dump = get_channel_data('call_answer')
            podio_api_call(crm_end_settings_url, channel_var_dump)
            do return end
        end
    end

    local run_once = session:getVariable('crm_call_run_once')

    session:execute("export", "crm_call_run_once=true")
    if run_once and run_once == 'true' then
        log.notice("Script aleady aware of this call, skipping...")
        do return end
    end

    domain_uuid = session:getVariable('domain_uuid')

    local domain_name = session:getVariable('domain_name')

    local settings = Settings.new(db, domain_name, domain_uuid)

    local crm_settings_enabled = settings:get('crm_call', 'enabled', 'boolean')

    if crm_settings_enabled ~= 'true' then
        log.info("CRM is not enabled. Exiting")
        do return end
    end

    local crm_start_settings_url = settings:get('crm_call', 'start_url', 'text')
    local crm_end_settings_url = settings:get('crm_call', 'end_url', 'text')

    session:execute("export", "crm_start_settings_url=" .. base64_enc(crm_start_settings_url))
    session:execute("export", "crm_end_settings_url=" .. base64_enc(crm_end_settings_url))
    
    local caller_id_number = session:getVariable('caller_id_number') or ""
    local destination_number = session:getVariable('destination_number') or ""

    -- Execute call_start to podio
    local channel_var_dump = get_channel_data('call_start')
    podio_api_call(crm_end_settings_url, channel_var_dump)
    -- End call_start to podio

    if destination_number ~= main_number then
        log.info("It's not a main number, exiting...")
        do return end
    end
    
    caller_id_number = caller_id_number:gsub("%D", "")
    caller_id_number = caller_id_number:sub(-10)

    if (caller_id_number ~= "" and #caller_id_number > 7) then

        local responce = contact_api_call(crm_start_settings_url, caller_id_number, true)
        log.notice("Response: " .. responce)
        local transfer_extension
        local first_name
        local last_name

        if (responce and responce:len() > 2) then

            transfer_extension, first_name, last_name = process_json_answer(responce)

            if first_name and #first_name > 0 and first_name ~= 'null' then
                session:execute("export", "crm_first_name=" .. first_name)
            end

            if last_name and #last_name > 0 and last_name ~= 'null' then
                session:execute("export", "crm_last_name=" .. last_name)
            end

            if transfer_extension then
                update_or_save(caller_id_number, transfer_extension)
                log.info("Transferring to " .. transfer_extension .. " with request")
                session:execute("transfer", transfer_extension .. " XML " .. domain_name)
                do return end
            end
        end

        transfer_extension = get_from_db(caller_id_number)
        if transfer_extension then
            log.info("Transferring to " .. transfer_extension .. " with cache")
            session:execute("transfer", transfer_extension .. " XML " .. domain_name)
            do return end
        end
    end

    transfer_extension = type_transfer_table['default']

    log.info("Transferring to " .. transfer_extension .. " with default option")
    session:execute("transfer", transfer_extension .. " XML " .. domain_name)
end