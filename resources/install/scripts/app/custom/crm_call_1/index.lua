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

function crm_api_call(url, data, is_return)

    local cmd_string = "curl " .. url .. "?phone_number=" .. data .. " connect-timeout 1 timeout 2 post"

    if is_return == nil then 
        cmd_string = "bgapi " .. cmd_string
    end

    return api:executeString(cmd_string)
end

-- Base64 encoding/decoding
-- encoding
function base_enc64(data)
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

function process_json_answer(responce)

    local json = freeswitch.JSON()
    local data = json:decode(responce)

    if (data == nil or data[1] == nil) then
        log.warning("Cannot process " .. responce .. " as json")
        return nil
    end

    data = data[1]

    if (data["route_call_to_extension"] and string.len(data["route_call_to_extension"]) > 0) then
        return data["route_call_to_extension"]
    end

    if (data["route_call_with_option"] and string.len(data["route_call_with_option"]) > 0) then
        return "option_" .. data["route_call_with_option"]
    end

    if (data['type'] and string.len(data['type']) > 0) then
        local data_type = string.lower(data['type'])
        data_type = data_type:gsub(" ", "_")

        return type_transfer_table[data_type]
    end
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

api = freeswitch.API()

local Settings = require "resources.functions.lazy_settings"
local Database = require "resources.functions.database"

db = Database.new('system')

assert(db:connected())

if (session:ready()) then

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

    session:execute("export", "crm_start_settings_url=" .. base_enc64(crm_start_settings_url))
    session:execute("export", "crm_end_settings_url=" .. base_enc64(crm_end_settings_url))
    
    local caller_id_number = session:getVariable('caller_id_number') or ""
    local destination_number = session:getVariable('destination_number') or ""

    if destination_number ~= main_number then
        log.info("It's not a main number, exiting...")
        do return end
    end
    
    caller_id_number = caller_id_number:gsub("%D", "")
    caller_id_number = caller_id_number:sub(-10)

    if (caller_id_number ~= "" and #caller_id_number > 7) then

        local responce = crm_api_call(crm_start_settings_url, caller_id_number, true)
        log.notice("Response: " .. responce)
        local transfer_extension

        if (responce and responce:len() > 2) then

            transfer_extension = process_json_answer(responce)
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