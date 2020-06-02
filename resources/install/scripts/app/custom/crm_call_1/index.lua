-- This function is called like app_custom.lua vtiger_connector
-- You MUST specify VTiger URL in Default (or Domain) settings.
-- Also it uses freeswitch curl command, so it also need to be loaded

-- Vars to specify
-- url
-- api_key

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


api = freeswitch.API()
json = freeswitch.JSON()

local Settings = require "resources.functions.lazy_settings"
local Database = require "resources.functions.database"

local db = Database.new('system')

assert(db:connected())

if (session:ready()) then

    local domain_name = session:getVariable('domain_name')
    local domain_uuid = session:getVariable('domain_uuid')

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
    caller_id_number = caller_id_number:gsub("%D", "")

    if (caller_id_number ~= "" and #caller_id_number > 7) then
        local responce = crm_api_call(crm_start_settings_url, caller_id_number, true)
        log.notice("Response: " .. responce)
    end
end