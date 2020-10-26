class ParameterLocationRetrieval {

    setUpLocationRetrievalButtons () {
        const locationRetrievalButtons = document.querySelectorAll(".location_info");
        const siteAccessListNodes = document.querySelectorAll("[siteaccess]");


        for (const button of locationRetrievalButtons) {

            let siteAccess = "";

            for (const siteAccessList of siteAccessListNodes) {
                siteAccess = siteAccessList.contains(button)? siteAccessList.getAttribute("siteaccess") : siteAccess;
            }

            this.resolveParameterNameToAttribute(button, siteAccess);
            button.onclick = (event) => {
                event.preventDefault();
                event.stopPropagation();

                this.locationRetrievalRequest(button);
            }
        }
    }

    resolveParameterNameToAttribute (targetButton, siteAccess = "") {
        if (targetButton) {
            let resolvedName = "";

            let parentKey = (targetButton.parentElement && targetButton.parentElement.classList.contains("param_list_keys"))?
                targetButton.parentElement : targetButton.previousElementSibling;

            while (parentKey) {
                if (parentKey.classList.contains("param_list_keys")) {
                    resolvedName = `${parentKey.getAttribute("key")}.${resolvedName}`;
                } else if (parentKey.previousElementSibling.classList.contains("param_list_keys")) {
                    parentKey = parentKey.previousElementSibling;
                    resolvedName = `${parentKey.getAttribute("key")}.${resolvedName}`;
                } else {
                    while (parentKey.previousElementSibling && !parentKey.classList.contains("param_list_keys")) {
                        parentKey = parentKey.previousElementSibling;
                    }

                    if (parentKey.classList.contains("param_list_keys")) {
                        continue;
                    }

                    break;
                }

                if (parentKey.classList.contains("top_nodes")) {

                    if (siteAccess && siteAccess.length > 0) {
                        const siteAccessAddBorder = resolvedName.indexOf(".");
                        resolvedName = `${resolvedName.substring(0,siteAccessAddBorder+1)}${siteAccess}${resolvedName.substring(siteAccessAddBorder)}`;
                    }

                    break;
                } else if (parentKey.parentElement) {
                    parentKey = parentKey.parentElement.previousElementSibling;
                }
            }

            resolvedName = resolvedName.substring(0,resolvedName.length-1);
            targetButton.setAttribute("fullParameterName", resolvedName);
        }
    }

    async locationRetrievalRequest(targetButton) {
        let parameterName = encodeURI(targetButton.getAttribute("fullparametername"));

        if (targetButton) {
            const res = await fetch("/cjw/config-processing/parameter_locations/"+parameterName, {
                method: "GET",
            });

            if (res) {
                console.log(await res.json());
            }
        }
    }
}
