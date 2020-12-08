class Utility {
  /**
   * Takes a given container and searches for the direct param_list_keys under the direct children
   * param_list_items within the container.
   *
   * @param {HTMLElement} containerNode The node in which to search for the keys.
   * @returns {array<HTMLElement>} Returns either the keys found in the children of the container or an empty array in case no keys are found.
   */
  getDirectKeyChildrenOfContainersDirectChildren(containerNode) {
    if (containerNode) {
      const result = [];

      if (!containerNode.children || containerNode.children.length === 0) {
        return result;
      }

      for (const child of containerNode.children) {
        if (child.classList.contains("param_list_items")) {
          if (
            child.children[0] &&
            child.children[0].classList.contains("param_list_keys")
          ) {
            result.push(child.children[0]);
          }
        }
      }

      return result;
    }
  }

  /**
   * Value counterpart to {@see getDirectKeyChildrenOfContainersDirectChildren}, which takes a given container element
   * and searches within its direct param_list_items children for value nodes.
   *
   * @param {HTMLElement} containerNode The given container node in which to search for values.
   * @returns {array<HTMLElement>} Returns an array with the found values or an empty array if no values have been found.
   */
  getValueChildrenOfContainersDirectChildren(containerNode) {
    if (containerNode) {
      const result = [];

      if (!containerNode.children || containerNode.children.length === 0) {
        return result;
      }

      for (const child of containerNode.children) {
        if (child.classList.contains("param_list_items")) {
          for (const grandChild of child.children) {
            if (grandChild.classList.contains("param_list_values")) {
              result.push(grandChild);
            }
          }
        } else if (child.classList.contains("param_list_values")) {
          result.push(child);
        }
      }

      return result;
    }
  }

  findCounterpartNode(originalNode, listOfPotentialCounterparts) {
    if (
      originalNode &&
      listOfPotentialCounterparts &&
      listOfPotentialCounterparts.length > 0
    ) {
      for (const potentialCounterpart of listOfPotentialCounterparts) {
        if (this.compareNodes(originalNode, potentialCounterpart)) {
          return potentialCounterpart;
        }
      }
    }

    return null;
  }

  compareNodes(originalNode, comparisonNode) {
    if (originalNode && comparisonNode && originalNode !== comparisonNode) {
      if (
        originalNode.classList.contains("param_list_keys") &&
        comparisonNode.classList.contains("param_list_keys") &&
        originalNode.getAttribute("key") ===
          comparisonNode.getAttribute("key") &&
        originalNode.classList.contains("favourite_key_entry") ===
          comparisonNode.classList.contains("favourite_key_entry")
      ) {
        if (
          originalNode.classList.contains("top_nodes") &&
          comparisonNode.classList.contains("top_nodes")
        ) {
          return true;
        }

        const nextKey = this.getParentKeyFromKey(originalNode);
        const nextComparisonKey = this.getParentKeyFromKey(comparisonNode);

        if (nextKey && nextComparisonKey) {
          return this.compareNodes(nextKey, nextComparisonKey);
        }
      }
    }

    return false;
  }

  getParentKeyFromKey(key) {
    if (key) {
      let keyParent;
      if (key.classList.contains("param_list_item")) {
        keyParent = key;
      } else {
        keyParent = key.parentElement;
      }

      if (keyParent.parentElement) {
        const firstChild = keyParent.parentElement.children[0];

        if (firstChild.classList.contains("param_list_keys")) {
          return firstChild;
        }
      }
    }

    return null;
  }

  createSVGElement(pathToIcon = null, pathAddition = "", useEzClasses = false) {
    const pathToResource =
      pathToIcon ?? "/bundles/ezplatformadminui/img/ez-icons.svg#";

    const svgElement = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "svg"
    );
    const useElement = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "use"
    );

    useElement.setAttributeNS(
      "http://www.w3.org/1999/xlink",
      "xlink:href",
      `${pathToResource}${pathAddition}`
    );

    svgElement.appendChild(useElement);

    if (useEzClasses) {
      svgElement.classList.add("ez-icon", "ez-icon--small");
    }

    return svgElement;
  }

  /**
   *
   * @param {string} url
   * @param {boolean} symfonyParameterMode
   * @param {string} method
   * @param {string} urlParameters
   * @returns {Promise<Response>}
   */
  async performFetchRequestWithoutBody(
    url,
    method = "GET",
    symfonyParameterMode = false,
    ...urlParameters
  ) {
    url = this.addInUrlParameters(url, urlParameters, symfonyParameterMode);
    url = encodeURI(url);

    return await fetch(url, {
      method: method,
    });
  }

  /**
   *
   * @param {string} url
   * @param {boolean} symfonyParameterMode
   * @param {string} method
   * @param body
   * @param {string} urlParameters
   * @returns {Promise<Response>}
   */
  async performFetchRequestWithBody(
    url,
    method = "POST",
    body,
    symfonyParameterMode = false,
    ...urlParameters
  ) {
    url = this.addInUrlParameters(url, urlParameters, symfonyParameterMode);
    url = encodeURI(url);
    body = JSON.stringify(body);

    return await fetch(url, {
      method: method,
      headers: {
        "Content-Type": "application/json",
      },
      body: body,
    });
  }

  addInUrlParameters(url, urlParameters, symfonyParameterMode = false) {
    if (urlParameters) {
      for (let urlParameter of urlParameters) {
        if (urlParameter) {
          if (symfonyParameterMode && !url.endsWith("/")) {
            url += "/";
          }

          url += urlParameter;
        }
      }
    }

    return url;
  }

  storeStateInUrl(state, stateValue = "true") {
    state = encodeURI(state);
    stateValue = encodeURI(stateValue);

    if (state && stateValue && !window.location.search.includes(state)) {
      state.replaceAll(/[?&]/g, "");

      state = window.location.search ? "&" + state : "?" + state;
      state = state.includes("=") ? state : state + "=" + stateValue;

      state = window.location.search + state;

      history.replaceState(
        "",
        document.title,
        window.location.pathname + state
      );
    }
  }

  alterStateInUrl(state, newValue) {
    const queryString = window.location.search;

    if (state && newValue) {
      if (queryString.includes(state)) {
        this.removeStateFromUrl(state);
      }

      this.storeStateInUrl(state, newValue);
    }
  }

  removeStateFromUrl(state) {
    const queryString = window.location.search;
    state = encodeURI(state);

    if (state && queryString.includes(state)) {
      let stateStartIndex = queryString.indexOf(state);

      let stateEndIndex = queryString.indexOf("&", stateStartIndex);

      if (stateEndIndex <= 0 || stateEndIndex < stateStartIndex) {
        stateEndIndex = queryString.length;
        --stateStartIndex;
      }

      const newUrl =
        window.location.pathname +
        queryString.replace(
          queryString.substring(stateStartIndex, stateEndIndex + 1),
          ""
        );

      history.replaceState("", document.title, newUrl);
    }
  }

  getStateFromUrl(state) {
    state = encodeURI(state);
    const queryString = window.location.search;

    if (state && queryString.includes(state)) {
      let stateValueStartIndex =
        queryString.indexOf("=", queryString.indexOf(state)) + 1;
      let stateValueEndIndex = queryString.indexOf("&", stateValueStartIndex);

      if (
        stateValueEndIndex === 0 ||
        stateValueEndIndex < stateValueStartIndex
      ) {
        stateValueEndIndex = queryString.length;
      }

      return queryString.substring(stateValueStartIndex, stateValueEndIndex);
    }

    return null;
  }
}
