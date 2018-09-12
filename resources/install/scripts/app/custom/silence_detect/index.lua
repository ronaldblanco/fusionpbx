-- Script to accept 2 vars
-- argv2 - Transfer to if silence detected
-- argv3 - Loops to detect silence. Default 10

require "app.custom.silence_detect.resources.functions.silence_detect_functions"
require "app.custom.silence_detect.resources.functions.wav"


-- Differece in 2 close samples to say, that change was done
silence_threshold = 100

-- How many silence_threshold to consider, that it's silence and not false-positive
threshold_total_hits = 3

-- Where to store temp files. Default = memory
tmp_dir = '/dev/shv/'

if session:ready() then

    local transfer_on_silence = argv[2] or nil
    
    if transfer_on_silence then

        local loop_count = argv[3] or 10

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

            freeswitch.consoleLog("NOTICE", "[silence_detect] Filename: " .. tmp_file_name .. " Loop:" .. i)
            session:execute("record_session", tmp_file_name)
            session:execute("playback", 'tone_stream://$${ringback}')
            session:execute("stop_record_session", tmp_file_name)

            -- Function to return true if is silence in file is detected
            is_silence_detected = silence_detect_file(tmp_file_name)
            os.remove(tmp_file_name)
            if (is_silence_detected == false) then
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
            freeswitch.consoleLog("NOTICE", "[silence_detect] Silence is detected for this call. Transferring to " .. transfer_on_silence)
            if (transfer_on_silence == 'hangup') then
                session:execute("hangup")
            else
                local domain_name = session:getVariable('domain_name') or ""
                session:execute("transfer", transfer_on_silence .. " XML " .. domain_name)
            end
        else
            freeswitch.consoleLog("NOTICE", "[silence_detect] Silence is not detected for this call. Continue dialplan")
        end    
    end
end