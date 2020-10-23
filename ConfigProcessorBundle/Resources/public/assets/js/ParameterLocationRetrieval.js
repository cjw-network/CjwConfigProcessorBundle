class ParameterLocationRetrieval {

    setUpLocationRetrievalButtons () {
        const locationRetrievalButtons = document.querySelectorAll(".location_info");

        for (const button of locationRetrievalButtons) {
            this.resolveParameterNameToAttribute(button);
        }
    }

    resolveParameterNameToAttribute (targetButton) {
        if (targetButton) {
            let resolvedName = "";

            let parentKey = (targetButton.parentElement && targetButton.parentElement.classList.contains("param_list_keys"))?
                targetButton.parentElement : targetButton.previousElementSibling;

            while (parentKey) {
                if (parentKey.classList.contains("param_list_keys")) {
                    resolvedName = `${parentKey.getAttribute("key")}.${resolvedName}`;
                } else {
                    break;
                }

                if (parentKey.classList.contains("top_nodes")) {
                    break;
                } else if (parentKey.parentElement) {
                    parentKey = parentKey.parentElement.previousElementSibling;
                }
            }

            resolvedName = resolvedName.substring(0,resolvedName.length-2);
            targetButton.setAttribute("fullParameterName", resolvedName);
        }
    }


}
