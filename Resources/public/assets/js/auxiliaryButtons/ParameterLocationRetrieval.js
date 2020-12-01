class ParameterLocationRetrieval {
  utility;

  constructor() {
    this.utility = new Utility();
  }

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

        let withSiteAccess = false;
        let siteAccess = document.querySelector("[siteaccess]");
        if (siteAccess && siteAccess.getAttribute("siteaccess").length > 0) {
          withSiteAccess = true;
        }

        this.locationRetrievalRequest(button, withSiteAccess);
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
          let keyAttribute = parentKey.getAttribute("key");

          if (parentKey.getAttribute("originalKey")) {
            keyAttribute = parentKey.getAttribute("originalKey");
          }

          if (siteAccess && resolvedName.length === 0) {
            keyAttribute = siteAccess + "." + keyAttribute;
          }

          resolvedName = `${keyAttribute}.${resolvedName}`;
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
          break;
        } else if (parentKey.parentElement) {
          parentKey = parentKey.parentElement.previousElementSibling;
        }
      }

      resolvedName = resolvedName.substring(0, resolvedName.length - 1);
      targetButton.setAttribute("fullParameterName", resolvedName);
    }
  }

  async locationRetrievalRequest(targetButton, withSiteAccess = false) {
    let parameterName = targetButton.getAttribute("fullparametername");
    withSiteAccess = "" + withSiteAccess;

    if (targetButton) {
      const res = await this.utility.performFetchRequestWithoutBody(
        "/cjw/config-processing/parameter_locations/",
        "GET",
        true,
        parameterName,
        withSiteAccess
      );

      if (res) {
        const responseJson = await res.json();
        let pathOverview = await this.buildLocationList(responseJson);

        if (!pathOverview) {
          pathOverview = await this.buildLocationList({
            unknown:
              "No path has been found for the current parameter. This might mean, that it does not originally belong to the current site access, default or global.",
          });
        } else {
          this.checkForActiveValue(targetButton.parentElement, pathOverview);
        }

        targetButton.parentElement.appendChild(pathOverview);
        targetButton.parentElement.classList.add("path_info_carrier");
        targetButton.innerText = "x";
        targetButton.classList.add("close_location_info");

        document.dispatchEvent(
          new CustomEvent("pathKeysAdded", {
            bubbles: true,
            detail: { pathInfoCarrier: targetButton.parentElement },
          })
        );

        targetButton.onclick = (event) => {
          event.preventDefault();
          event.stopPropagation();

          this.removePathInfo(targetButton.parentElement, pathOverview);
          targetButton.innerText = "i";
          targetButton.classList.remove("close_location_info");
        };
      }
    }
  }

  buildLocationList(responseBody) {
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
          keyContainer.setAttribute("path", path);
          keyContainer.title = "copy path";
          valueContainer.innerText = value;

          valueContainer.classList.add("path_info_value");
          keyContainer.classList.add("path_info_key");

          carrier.appendChild(keyContainer);
          carrier.appendChild(valueContainer);
          carrier.classList.add("path_info");
          container.appendChild(carrier);
        }

        return container;
      } else {
        return paths;
      }
    }
  }

  checkForActiveValue(keyForWhichToSearch, valueNodeList) {
    if (
      keyForWhichToSearch &&
      keyForWhichToSearch.classList.contains("param_list_keys") &&
      valueNodeList
    ) {
      let potentialValues = keyForWhichToSearch.querySelectorAll(
        ".inline_value"
      );
      const allValuesFromNodeList = valueNodeList.querySelectorAll(
        ".path_info_value"
      );

      if (!potentialValues || potentialValues.length === 0) {
        const keyParent = keyForWhichToSearch.parentElement;
        potentialValues = keyParent.querySelectorAll(".param_list_values");
      }

      for (const actualValue of potentialValues) {
        let actualValuesValue = actualValue.getAttribute("value");

        if (actualValue.getAttribute("originalValue")) {
          actualValuesValue = actualValue.getAttribute("originalValue");
        }

        for (const value of allValuesFromNodeList) {
          let valuesAreEqual = this.compareGivenValues(
            actualValuesValue,
            value.innerText
          );

          if (
            !valuesAreEqual &&
            !(value.innerText === "[object Object]") &&
            !new RegExp(/^%.*%$/).test(value.innerText)
          ) {
            value.style.opacity = "0.75";
            const keyToValue = value.parentElement.children[0];
            keyToValue.style.opacity = "0.75";
            keyToValue.style.borderBottom = "1px transparent";
          } else {
            value.style.fontWeight = "bold";
          }
        }
      }
    }
  }

  /**
   * Compares two given values (strings) and determines whether the two are equal or not. It does also assume,
   * that (is only valid for the second value) if a value contains commas that it could be a list of values chained together
   * and thus returns whether the first given value is contained in the list.
   *
   * @param {string} nodesValue One given value of the comparison.
   * @param {string} compareValue The second given value of the comparison.
   * @returns {boolean} Returns a boolean which states whether the two values are equal or not.
   */
  compareGivenValues(nodesValue, compareValue) {
    if (compareValue.includes(",")) {
      const multipleInnerValues = compareValue.split(",");

      if (multipleInnerValues.includes(nodesValue)) {
        return true;
      }
    }

    return compareValue === nodesValue;
  }

  removePathInfo(targetButtonParent, pathContainerToRemove) {
    if (targetButtonParent && pathContainerToRemove) {
      targetButtonParent.removeChild(pathContainerToRemove);
    }

    targetButtonParent.classList.remove("path_info_carrier");

    const targetButton = targetButtonParent.querySelector(
      ".close_location_info"
    );

    if (targetButton) {
      targetButton.onclick = (event) => {
        event.preventDefault();
        event.stopPropagation();

        let withSiteAccess = false;
        if (document.querySelector("[siteaccess]")) {
          withSiteAccess = true;
        }

        this.locationRetrievalRequest(targetButton, withSiteAccess);
      };
    }
  }
}
