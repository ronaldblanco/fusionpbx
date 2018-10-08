-- Script to accept 2 vars
-- argv2 - Transfer to if silence detected
-- argv3 - Loops to detect silence. Default 10
-- argv4 - algo is used. 
--          samples - simple algo with 2 parameters - silence_threshold (argv5) and threshold_total_hits (argv6)
--          lines - trying to build silence/peaks ratio. silence_threshold (argv5) line_peak_ratio (argv6) quantinizer (argv7)

require "app.custom.silence_detect.resources.functions.silence_detect_functions"
require "app.custom.silence_detect.resources.functions.wav"
require "app.custom.silence_detect.resources.argparse.wav"


-- Where to store temp files. Default = memory
tmp_dir = '/dev/shm/'

if session:ready() then

    local argparse = require "argparse"

    local parser = argparse("silence_detect", "A silence detect script for FusionPBX")
    parser:argument("script_name") 
        : args(1)
    parser:option("-a --algo") 
        : default "samples"
    parser:option("-l --loops") 
        : default "5"
    parser:option("-e --exclude-pattern") 
        : count "*"
    parser:option("-i --include-pattern") 
        : count "*"
    parser:option("-c --callerid-length")
        : count "*"
    parser:option("-t --transfer-to")
        : default "hangup"
    parser:option("--hangup-reason")
        :default ""
    parser:argument("algo_opts") 
        : args("*")

    local args = parser:parse()

    local algo = args['algo']
    local algo_opts = args['algo_opts'] or ''

    local transfer_on_silence = args['transfer-to']
    local loop_count = tonumber(args['loops'])

    local caller_id_number = session:getVariable('caller_id_number') or ""

    if (args['callerid-length']) then
        local caller_id_lenght_matched = false
        for _,v in pairs(args['callerid-length']) do
            if (tomumber(v) == #caller_id_number) then
                caller_id_lenght_matched = true
                break
            end
        end
        if (caller_id_lenght_matched == false) then
            session:setVariable("silence_detect", "CallerID lenght mismatch")
            freeswitch.consoleLog("NOTICE", "[silence_detect] CallerID lenght mismatch")
            do return end
        end
    end

    if (args['include-pattern']) then
        local include_pattern_match = false
        for _,v in pairs(args['include-pattern']) do
            if (string.match(caller_id_number, v)) then
                include_pattern_match = true
                break
            end
        end
        if (include_pattern_match == false) then
            session:setVariable("silence_detect", "Include pattern mismatch")
            freeswitch.consoleLog("NOTICE", "[silence_detect] Include pattern mismatch")
            do return end
        end
    end

    if (args['exclude-pattern']) then
        local exclude_pattern_match = false
        for _,v in pairs(args['exclude-pattern']) do
            if (string.match(caller_id_number, v)) then
                exclude_pattern_match = true
                break
            end
        end
        if (exclude_pattern_match) then
            session:setVariable("silence_detect", "Exclude pattern match")
            freeswitch.consoleLog("NOTICE", "[silence_detect] Exclude pattern match")
            do return end
        end
    end

    local record_append = session:getVariable('RECORD_APPEND') or nil
    local record_read_only = session:getVariable('RECORD_READ_ONLY') or nil
    local record_stereo = session:getVariable('RECORD_STEREO') or nil

    local tmp_file_name = session:getVariable('call_uuid') or "tmp_file"
    local is_silence_detected

    tmp_file_name = tmp_dir .. tmp_file_name .. '_sil_det.wav'

    session:setVariable('RECORD_READ_ONLY', 'true')
    session:setVariable('RECORD_APPEND', 'false')
    session:setVariable('RECORD_STEREO', 'false')
    -- Answer the call
    session:answer()

    for i = 1, loop_count do

        freeswitch.consoleLog("NOTICE", "[silence_detect] Loop:" .. i)
        session:execute("record_session", tmp_file_name)
        session:execute("playback", 'tone_stream://$${ringback}')
        session:execute("stop_record_session", tmp_file_name)

        -- Function to return true if is silence in file is detected
        is_silence_detected, silence_detect_debug_info = silence_detect_file(tmp_file_name, algo, algo_opts)
        session:setVariable("silence_detect_" .. algo .. "_" .. i, silence_detect_debug_info)
        os.remove(tmp_file_name)
        if (is_silence_detected == false) then
            loop_detected = i
            break
        end
    end

    -- Restore variables
    session:execute("unset", "RECORD_READ_ONLY")
    if record_append then
        session:setVariable('RECORD_APPEND', record_append)
    end
    if record_read_only then
        session:setVariable('RECORD_READ_ONLY', record_read_only)
    end
    if record_stereo then
        session:setVariable('RECORD_STEREO', record_stereo)
    end

    if (is_silence_detected) then
        freeswitch.consoleLog("NOTICE", "[silence_detect] Silence is detected. Transferring to " .. transfer_on_silence)
        if (transfer_on_silence == 'hangup') then
            session:execute("hangup", args['hangup-reason'])
        else
            local domain_name = session:getVariable('domain_name') or ""
            session:execute("transfer", transfer_on_silence .. " XML " .. domain_name)
        end
    end
end