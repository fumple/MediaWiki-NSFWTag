// Listen for new ajax requests
$(document).on("ajaxSend", (event, jqxhr, options) => {
    if(options.contentType.startsWith("application/x-www-form-urlencoded;")) {
        // parse data
        let data = new URLSearchParams(options.data);

        // if we are previewing a page
        if(data.get("action") == "parse" && data.get("preview") == "true") {
            // and the checkbox exists and is checked
            let nsfwCheckbox = $("#wpNSFWTagShow")[0]
            if(nsfwCheckbox != null && nsfwCheckbox.checked) {
                // add it to the data
                data.set("shownsfwcheckbox", '')
                options.data = data.toString()
            }
        }
    }
})