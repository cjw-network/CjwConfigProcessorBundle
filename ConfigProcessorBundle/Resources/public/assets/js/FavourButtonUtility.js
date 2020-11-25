class FavourButtonUtility {
  favourButtons;
  globalFavourCommitButton;
  utility;

  constructor() {
    this.favourButtons = document.querySelectorAll(".favour_parameter");
    this.globalFavourCommitButton = document.querySelector(
      "#commit_favourites"
    );
    this.utility = new Utility();
  }

  setUpFavourButtons() {
    if (this.favourButtons && this.globalFavourCommitButton) {
      this.globalFavourCommitButton.onclick = this.handleGlobalFavourCommitClick.bind(
        this
      );

      for (const favourButton of this.favourButtons) {
        favourButton.onclick = this.handleFavourClick.bind(this);
      }
    }
  }

  handleGlobalFavourCommitClick(event) {
    event.preventDefault();
    event.stopPropagation();

    this.commitFavouritesToBackend();
  }

  handleFavourClick(event) {
    event.preventDefault();
    event.stopPropagation();

    const favourButton = event.currentTarget;
    const favourButtonParent = favourButton.parentElement;

    if (favourButtonParent) {
      this.setOrRemoveFavourite(favourButton, favourButtonParent);
      this.favourModelSwitch(favourButton, favourButtonParent);
    }
  }

  async commitFavouritesToBackend() {
    const nodeListOfFavourites = document.querySelectorAll(
      "[favourite]:not(.favourite_key_entry)"
    );
    const parameterNameArray = [];

    if (nodeListOfFavourites && nodeListOfFavourites.length > 0) {
      parameterNameArray.push(
        ...this.buildFavouriteKeysForBackend(nodeListOfFavourites)
      );
    }

    if (parameterNameArray.length > 0) {
      await this.utility.performFetchRequestWithBody(
        "/cjw/config-processing/parameter_list/save/favourites",
        "POST",
        parameterNameArray
      );
    }
  }

  buildFavouriteKeysForBackend(nodeListOfFavourites) {
    const parameterNameList = [];

    if (nodeListOfFavourites) {
      for (const favourite of nodeListOfFavourites) {
        const locationRetrievalButton = favourite.querySelector(
          ".location_info"
        );

        if (locationRetrievalButton) {
          const parameterName = locationRetrievalButton.getAttribute(
            "fullparametername"
          );

          if (!parameterNameList.includes(parameterName)) {
            parameterNameList.push(parameterName);
          }
        }
      }
    }

    return parameterNameList;
  }

  setOrRemoveFavourite(favourButton, favourButtonParent) {
    if (favourButton) {
      if (favourButtonParent.getAttribute("favourite")) {
        favourButtonParent.removeAttribute("favourite");
      } else {
        favourButtonParent.setAttribute("favourite", "true");
      }
    }
  }

  favourModelSwitch(targetButton, targetButtonParent) {
    if (targetButton && targetButtonParent) {
      let favorButtonIcon;

      if (targetButtonParent.getAttribute("favourite") === "true") {
        favorButtonIcon = this.utility.createSVGElement(
          null,
          "bookmark-active",
          true
        );
      } else {
        favorButtonIcon = this.utility.createSVGElement(null, "bookmark", true);
      }

      const previousIcon = targetButton.querySelector("svg");

      if (previousIcon) {
        targetButton.removeChild(previousIcon);
      }

      targetButton.appendChild(favorButtonIcon);
    }
  }
}
