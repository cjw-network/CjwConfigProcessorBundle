class ParameterLocationRetrieval {
  setUpLocationRetrievalButtons() {
    const locationRetrievalButtons = document.querySelectorAll(
      ".location_info"
    );
    const siteAccessListNodes = document.querySelectorAll("[siteaccess]");

    for (const button of locationRetrievalButtons) {
      let siteAccess = "";

      for (const siteAccessList of siteAccessListNodes) {
        siteAccess = siteAccessList.contains(button)
          ? siteAccessList.getAttribute("siteaccess")
          : siteAccess;
      }

      this.resolveParameterNameToAttribute(button, siteAccess);

      button.onclick = (event) => {
        event.preventDefault();
        event.stopPropagation();

        this.locationRetrievalRequest(button);
      };
    }
  }

  resolveParameterNameToAttribute(targetButton, siteAccess = "") {
    if (targetButton) {
      let resolvedName = "";

      let parentKey =
        targetButton.parentElement &&
        targetButton.parentElement.classList.contains("param_list_keys")
          ? targetButton.parentElement
          : targetButton.previousElementSibling;

      while (parentKey) {
        if (parentKey.classList.contains("param_list_keys")) {
          resolvedName = `${parentKey.getAttribute("key")}.${resolvedName}`;
        } else if (
          parentKey.previousElementSibling.classList.contains("param_list_keys")
        ) {
          parentKey = parentKey.previousElementSibling;
          resolvedName = `${parentKey.getAttribute("key")}.${resolvedName}`;
        } else {
          while (
            parentKey.previousElementSibling &&
            !parentKey.classList.contains("param_list_keys")
          ) {
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
            resolvedName = `${resolvedName.substring(
              0,
              siteAccessAddBorder + 1
            )}${siteAccess}${resolvedName.substring(siteAccessAddBorder)}`;
          }

          break;
        } else if (parentKey.parentElement) {
          parentKey = parentKey.parentElement.previousElementSibling;
        }
      }

      resolvedName = resolvedName.substring(0, resolvedName.length - 1);
      targetButton.setAttribute("fullParameterName", resolvedName);
    }
  }

  async locationRetrievalRequest(targetButton) {
    let parameterName = encodeURI(
      targetButton.getAttribute("fullparametername")
    );

    if (targetButton) {
      const res = await fetch(
        "/cjw/config-processing/parameter_locations/" + parameterName,
        {
          method: "GET",
        }
      );

      if (res) {
        const responseJson = await res.json();
        // console.log(responseJson);
        const pathOverview = await this.buildLocationList(responseJson);

        if (pathOverview) {
          targetButton.parentElement.appendChild(pathOverview);
          targetButton.innerText = "X";
          targetButton.classList.remove("location_info");
          targetButton.classList.add("close_location_info");

          targetButton.onclick = (event) => {
            event.preventDefault();
            event.stopPropagation();

            this.removePathInfo(targetButton.parentElement, pathOverview);
            targetButton.innerText = "i";
            targetButton.classList.remove("close_location_info");
            targetButton.classList.add("location_info");
          };
        } else {
          alert("No path could be found for the chosen parameter!");
          targetButton.disabled = true;
        }
      }
    }
  }

  async buildLocationList(responseBody) {
    if (responseBody) {
      const paths = Object.keys(responseBody);

      if (paths && paths.length > 0) {
        const container = document.createElement("div");

        for (const path of paths) {
          const keyContainer = document.createElement("span");
          const carrier = document.createElement("div");
          const valueContainer = document.createElement("span");
          let value = responseBody[path];

          keyContainer.innerText = path + ": ";
          valueContainer.innerText = value;

          valueContainer.classList.add("path_info_value");
          keyContainer.classList.add("path_info_key");

          carrier.appendChild(keyContainer);
          carrier.appendChild(valueContainer);
          carrier.classList.add("path_info");
          container.appendChild(carrier);
        }

        // const newDocument = await window.open("","ParameterLocations")
        // newDocument.document.querySelector("body").appendChild(container);

        return container;
      } else {
        return paths;
      }
    }
  }

  removePathInfo(targetButtonParent, pathContainerToRemove) {
    if (targetButtonParent && pathContainerToRemove) {
      targetButtonParent.removeChild(pathContainerToRemove);
    }

    const targetButton = targetButtonParent.querySelector(
      ".close_location_info"
    );

    if (targetButton) {
      targetButton.onclick = (event) => {
        event.preventDefault();
        event.stopPropagation();

        this.locationRetrievalRequest(targetButton);
      };
    }
  }
}
