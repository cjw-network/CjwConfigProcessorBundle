class ParameterDisplay {
  utility;

  constructor() {
    if (document.querySelector(".first_list, .second_list")) {
      this.utility = new Utility();
    }
  }

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
            ".param_list_items, .param_list_values:not(.inline_value)"
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

  getListEntryNodes(targetNode, counterClick = false) {
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

      if (this.utility && !counterClick) {
        this.handleSynchronousClicking(targetNode);
      }
    }
  }

  closeListEntryNodes(targetNode, counterClick = false) {
    if (targetNode && targetNode.children.length > 0) {
      const toggler = targetNode.querySelector(
        ".param_list_keys > .param_item_toggle"
      );

      if (toggler) {
        this.setTogglerSymbol("next", toggler);
      }

      const childNodes = targetNode.querySelectorAll(
        ".param_list_items, .param_list_values:not(.inline_value)"
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

      if (this.utility && !counterClick) {
        this.handleSynchronousClicking(targetNode, true);
      }
    }
  }

  handleSynchronousClicking(targetNode, closeAgain = false) {
    if (this.utility) {
      const keyOfTargetNode = targetNode.children[0];
      const listOfPotentialNodes = document.querySelectorAll(
        `[key="${keyOfTargetNode.getAttribute("key")}"]`
      );
      const counterpartNode = this.utility.findCounterpartNode(
        keyOfTargetNode,
        listOfPotentialNodes
      );

      if (counterpartNode) {
        if (!closeAgain) {
          this.getListEntryNodes(counterpartNode.parentElement, true);
        } else {
          this.closeListEntryNodes(counterpartNode.parentElement, true);
        }
      }
    }
  }

  setAppropriateOnClick(node) {
    let doesHaveChildren = false;

    for (const child of node.children) {
      this.clearAttributes(child);
      if (
        child.classList.contains("param_list_items") ||
        child.classList.contains("param_list_values")
      ) {
        doesHaveChildren = true;
        break;
      }
    }

    if (node.classList.contains("param_list_items") && !doesHaveChildren) {
      const keysWithoutChildren = node.querySelectorAll(".param_list_keys");

      for (const key of keysWithoutChildren) {
        if (key.querySelector(".param_item_toggle")) {
          key.removeChild(key.querySelector(".param_item_toggle"));
        }

        if (key.querySelector(".open_subtree")) {
          key.removeChild(key.querySelector(".open_subtree"));
        }

        key.classList.add("param_list_key_without_child");

        key.onclick = (event) => {
          event.preventDefault();
          event.stopPropagation();
        };
      }
      return;
    }

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

  clearAttributes(node) {
    if (node) {
      if (node.classList.contains("param_list_keys")) {
        let replacementText = node.getAttribute("key");
        const symbolArray = this.getMissingAndReplacementSymbols(
          replacementText
        );

        if (symbolArray.length > 0) {
          node.setAttribute("originalKey", node.getAttribute("key"));

          let missingSymbol = null;
          for (const symbol of symbolArray) {
            if (!missingSymbol) {
              missingSymbol = symbol;
            } else {
              replacementText = replacementText.replaceAll(
                missingSymbol,
                symbol
              );
              missingSymbol = null;
            }
          }

          node.setAttribute("key", replacementText);
        }

        const inlineValueChild = node.querySelector(".inline_value");
        if (inlineValueChild) {
          this.clearAttributes(inlineValueChild);
        }
      } else if (node.classList.contains("param_list_values")) {
        let replacementText = node.getAttribute("value");
        const symbolArray = this.getMissingAndReplacementSymbols(
          replacementText
        );

        if (symbolArray.length > 0) {
          node.setAttribute("originalValue", node.getAttribute("value"));

          let missingSymbol = null;
          for (const symbol of symbolArray) {
            if (!missingSymbol) {
              missingSymbol = symbol;
            } else {
              replacementText = replacementText.replaceAll(
                missingSymbol,
                symbol
              );
              missingSymbol = null;
            }
          }
        }

        node.setAttribute("value", replacementText);
      }
    }
  }

  getMissingAndReplacementSymbols(nodeAttribute) {
    const symbolArray = [];

    if (nodeAttribute.includes("\\")) {
      symbolArray.push("\\", " ");
    }

    if (nodeAttribute.includes('"')) {
      symbolArray.push('"', "'");
    }

    return symbolArray;
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
