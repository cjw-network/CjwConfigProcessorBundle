class ParameterBranchDisplay {
  subTreeButtons;

  constructor(parameterToFocus) {
    if (parameterToFocus && parameterToFocus.length > 0) {
      this.subTreeButtons = parameterToFocus;
    } else {
      this.subTreeButtons = [];
    }
  }

  subTreeViewOpenClickListener() {
    for (const subTreeButton of this.subTreeButtons) {
      subTreeButton.onclick = (event) => {
        event.preventDefault();
        event.stopPropagation();

        this.openSubTreeInSeparateView(
          subTreeButton.parentElement.parentElement
        );
      };
    }
  }

  subTreeOpenClickListener() {
    for (const subTreeButton of this.subTreeButtons) {
      subTreeButton.onclick = (event) => {
        event.preventDefault();
        event.stopPropagation();

        this.openSubTree(subTreeButton.parentElement.parentElement);
        // this.flipSubtreeAction(subTreeButton, true);
      };
    }
  }

  //---------------------------------------------------------------------------------------------------------------
  // Subtree-Viewport
  //---------------------------------------------------------------------------------------------------------------

  openSubTreeInSeparateView(nodeToFocus) {
    const viewPort = document.createElement("div");
    viewPort.classList.add("subtree_viewport", "ez-sticky-container");

    viewPort.appendChild(this.createSubTreeHeader());
    viewPort.appendChild(this.prepareSubTreeContent(nodeToFocus));

    const viewContainer = document.querySelector(".cjw_main_body");

    const formerView = viewContainer?.querySelector(".subtree_viewport");
    if (formerView) {
      viewContainer.removeChild(formerView);
    }

    const rightSideMenu = document.querySelector(".ez-context-menu");

    if (rightSideMenu) {
      viewContainer?.insertBefore(viewPort, rightSideMenu);
    } else {
      viewContainer?.appendChild(viewPort);
    }
  }

  createSubTreeHeader() {
    const subtreeHeader = document.createElement("div");
    subtreeHeader.classList.add("subtree_header", "ez-sticky-container");

    const subtreeHeadline = document.createElement("span");
    subtreeHeadline.innerText = "Subtree-View:";
    subtreeHeadline.classList.add("subtree_headline");

    const subtreeCloser = document.createElement("button");
    subtreeCloser.innerText = "X";
    subtreeCloser.classList.add("subtree_closer");
    subtreeCloser.onclick = (event) => {
      event.preventDefault();
      event.stopPropagation();

      this.removeSeparateSubtreeViewer();
    };

    subtreeHeader.appendChild(subtreeHeadline);
    subtreeHeader.appendChild(subtreeCloser);

    return subtreeHeader;
  }

  prepareSubTreeContent(nodeToFocus) {
    const duplicatedSubtree = nodeToFocus.cloneNode(true);

    for (const child of duplicatedSubtree.querySelectorAll(".dont_display")) {
      child.classList.remove("dont_display");
    }

    this.cleanUpSubtreeNodes(duplicatedSubtree);

    return duplicatedSubtree;
  }

  cleanUpSubtreeNodes(upperNode) {
    if (upperNode) {
      const keys = upperNode.querySelectorAll(".param_list_keys");

      for (const key of keys) {
        // const toggler = key.querySelector(".param_item_toggle");
        //
        // if (toggler) {
        //   key.removeChild(toggler);
        // }

        const subtreeViewButton = key.querySelector(".open_subtree");

        if (subtreeViewButton) {
          key.removeChild(subtreeViewButton);
        }

        const locationRetrieverButton = key.querySelector(".location_info");

        if (locationRetrieverButton) {
          key.removeChild(locationRetrieverButton);
        }
      }
    }
  }

  removeSeparateSubtreeViewer() {
    const mainBody = document.querySelector(".cjw_main_body");

    if (mainBody) {
      const viewport = document.querySelector(".subtree_viewport");

      if (viewport) {
        mainBody.removeChild(viewport);
      }
    }
  }

  //---------------------------------------------------------------------------------------------------------------
  // Subtree-Open
  //---------------------------------------------------------------------------------------------------------------

  openSubTree(nodeToFocus) {
    // const searchBar = document.querySelector("#cjw_searchbar");
    const searchLimiter = nodeToFocus.querySelector(".param_list_keys");

    // if (searchBar) {
    //   searchBar.value = searchLimiter
    //     ? searchLimiter.getAttribute("key") + ":"
    //     : "";
    // }

    this.displayEntireBranch(nodeToFocus);

    if (document.querySelector(".second_list")) {
      // let testText = `[key="${searchLimiter.getAttribute("key")}"]`;
      const desiredNodes = document.querySelectorAll(
        `[key="${searchLimiter.getAttribute("key")}"]`
      );

      if (desiredNodes.length > 1) {
        const desiredNode =
          desiredNodes[0].parentElement === nodeToFocus
            ? desiredNodes[1].parentElement
            : desiredNodes[0].parentElement;

        this.displayEntireBranch(desiredNode);
      }
    }
  }

  displayEntireBranch(nodeToFocus) {
    if (nodeToFocus) {
      let childNodes = nodeToFocus.querySelectorAll(".param_list_items");
      childNodes = childNodes ? Array.from(childNodes) : [];
      childNodes.push(nodeToFocus);

      this.asynchronouslyDisplayEntireBranch(childNodes);
    }
  }

  asynchronouslyDisplayEntireBranch(nodeList) {
    if (nodeList && nodeList.length > 0) {
      const concurrentNodes =
        nodeList.length > 40
          ? nodeList.splice(0, 40)
          : nodeList.splice(0, nodeList.length);

      for (const node of concurrentNodes) {
        let event = new Event("click");
        node.dispatchEvent(event);
      }

      if (nodeList.length > 0) {
        setTimeout(() => {
          this.asynchronouslyDisplayEntireBranch(nodeList);
        });
      }
    }
  }
}
