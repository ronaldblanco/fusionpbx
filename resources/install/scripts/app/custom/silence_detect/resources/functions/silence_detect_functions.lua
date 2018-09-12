function silence_detect_samples(samples) 
	local first_sample = samples[1]
	local hits = 0

	for i = 2, #samples do
		if (math.abs(first_sample - samples[i]) > silence_threshold) then
			if (hits >= threshold_total_hits) then
				return false
			end
			hits = hits + 1
		end
		first_sample = samples[i]
	end

	return true
end


function file_exists(name)
   local f=io.open(name,"r")
   if f~=nil then io.close(f) return true else return false end
end

function silence_detect_file(filename)

	local file_reader = wav.create_context(filename, 'r')
	
	if (file_reader == false) then
		return true
	end

    file_reader.set_position(0)

    -- Read only channel 1
    local samples = file_reader.get_samples(math.floor(file_reader.get_samples_per_channel()) - 1)[1]

    file_reader.close_context()

    return silence_detect_samples(samples)
end