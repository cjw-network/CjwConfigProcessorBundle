class ParameterDisplay {
  paramBranchDisplay;

  cleanUpList() {
    const topNodes = document.querySelectorAll(
      ".param_list > .param_list_items"
    );

    this.setTopNodesAsynchronously(0, topNodes);
  }

  async setTopNodesAsynchronously(counter, nodeList) {
    if (nodeList && nodeList.length > counter >= 0) {
      do {
        const topNodeEntry = nodeList[counter];

        if (topNodeEntry) {
          this.setAppropriateOnClick(topNodeEntry);
          topNodeEntry.classList.remove("dont_display");

          const dontDisplayChildNodes = topNodeEntry.querySelectorAll(
            ".param_list_items, .param_list_values"
          );

          setTimeout(() => {
            this.cleanUpChildNodesAsynchronously(0, dontDisplayChildNodes);
          });

          const topKey = topNodeEntry.querySelector(".param_list_keys");

          if (topKey) {
            topKey.classList.add("top_nodes");
          }
        }

        ++counter;
      } while (counter < nodeList.length && counter % 30 !== 0);

      if (counter < nodeList.length) {
        setTimeout(() => {
          this.setTopNodesAsynchronously(counter, nodeList);
        });
      }
    }
  }

  cleanUpChildNodesAsynchronously(counter, nodeList) {
    if (nodeList && nodeList.length > counter >= 0) {
      do {
        const currentNode = nodeList[counter];

        if (currentNode) {
          this.setAppropriateOnClick(currentNode);
          currentNode.style.marginLeft += "12px";
        }

        ++counter;
      } while (counter < nodeList.length && counter % 40 !== 0);

      if (counter < nodeList.length) {
        setTimeout(() => {
          this.cleanUpChildNodesAsynchronously(counter, nodeList);
        });
      }
    }
  }

  getListEntryNodes(targetNode) {
    if (targetNode && targetNode.children.length > 0) {
      const toggler = targetNode.querySelector(
        ".param_list_keys > .param_item_toggle"
      );

      if (toggler) {
        this.setTogglerSymbol("down", toggler);
      }

      for (const entry of targetNode.children) {
        entry.classList.remove("dont_display");
      }

      targetNode.onclick = (event) => {
        event.stopPropagation();
        event.preventDefault();

        this.closeListEntryNodes(event.currentTarget);
      };
    }
  }

  closeListEntryNodes(targetNode) {
    if (targetNode && targetNode.children.length > 0) {
      const toggler = targetNode.querySelector(
        ".param_list_keys > .param_item_toggle"
      );

      if (toggler) {
        this.setTogglerSymbol("next", toggler);
        // toggler.innerText = "+";
      }

      const childNodes = targetNode.querySelectorAll(
        ".param_list_items, .param_list_values"
      );

      for (const entry of childNodes) {
        entry.classList.add("dont_display");

        const toggler = entry.querySelector(
          ".param_list_keys > .param_item_toggle"
        );

        if (toggler) {
          this.setTogglerSymbol("next", toggler);
        }

        entry.onclick = (event) => {
          event.preventDefault();
          event.stopPropagation();

          this.getListEntryNodes(event.currentTarget);
        };
      }

      targetNode.onclick = (event) => {
        event.stopPropagation();
        event.preventDefault();

        this.getListEntryNodes(event.currentTarget);
      };
    }
  }

  setAppropriateOnClick(node) {
    if (node.classList.contains("param_list_items")) {
      node.onclick = (event) => {
        event.preventDefault();
        event.stopPropagation();

        this.getListEntryNodes(event.currentTarget);
      };
    } else if (node.classList.contains("param_list_values")) {
      node.onclick = (event) => {
        event.preventDefault();
        event.stopPropagation();
      };
    }
  }

  setTogglerSymbol(nextOrDown, togglerNode) {
    if (
      togglerNode &&
      nextOrDown &&
      (nextOrDown === "next" || nextOrDown === "down")
    )
      for (const child of togglerNode.children) {
        togglerNode.removeChild(child);
      }

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
      `/bundles/ezplatformadminui/img/ez-icons.svg#caret-${nextOrDown}`
    );

    svgElement.appendChild(useElement);
    svgElement.classList.add("ez-icon", "ez-icon--small");

    togglerNode.appendChild(svgElement);
  }
}
