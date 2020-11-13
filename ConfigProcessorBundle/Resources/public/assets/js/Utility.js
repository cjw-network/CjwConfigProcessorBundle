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
        originalNode.getAttribute("key") === comparisonNode.getAttribute("key")
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
      }

      keyParent = key.parentElement;

      if (keyParent.parentElement) {
        const firstChild = keyParent.parentElement.children[0];

        if (firstChild.classList.contains("param_list_keys")) {
          return firstChild;
        }
      }
    }

    return null;
  }
}