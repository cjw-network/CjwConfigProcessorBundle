class FavourButtonUtility {
  favourButtons;
  dedicatedFavouriteView;
  utility;

  constructor() {
    this.favourButtons = document.querySelectorAll(".favour_parameter");
    this.dedicatedFavouriteView = document.querySelector("[list=favourites]");
    this.utility = new Utility();
  }

  setUpFavourButtons() {
    if (this.favourButtons) {
      for (const favourButton of this.favourButtons) {
        favourButton.onclick = this.handleFavourClick.bind(this);

        if (
          this.dedicatedFavouriteView ||
          favourButton.getAttribute("favourite") === "true"
        ) {
          const favourButtonParent = favourButton.parentElement;
          this.setOrRemoveFavourite(favourButton, favourButtonParent);
          this.switchFavourButtonModel(favourButton, favourButtonParent);
        }
      }
    }
  }

  handleFavourClick(event) {
    event.preventDefault();
    event.stopPropagation();

    const favourButton = event.currentTarget;
    const favourButtonParent = favourButton.parentElement;

    if (favourButtonParent) {
      const toRemove = favourButtonParent.getAttribute("favourite") === "true";

      this.setOrRemoveFavourite(favourButton, favourButtonParent);
      this.switchFavourButtonModel(favourButton, favourButtonParent);

      const favourSVG = favourButton.querySelector("svg");
      favourSVG.style.fill = "orange";

      this.saveOrRemoveFavouriteToBackend(
        favourButton,
        favourButtonParent,
        toRemove
      );
    }
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

  switchFavourButtonModel(targetButton, targetButtonParent) {
    if (targetButton && targetButtonParent) {
      let favorButtonIcon;

      if (targetButtonParent.getAttribute("favourite") === "true") {
        favorButtonIcon = this.utility.createSVGElement(
          null,
          "bookmark-active",
          true
        );

        targetButton.title = "remove favourite";
      } else {
        favorButtonIcon = this.utility.createSVGElement(null, "bookmark", true);
        targetButton.title = "mark as favourite";
      }

      const previousIcon = targetButton.querySelector("svg");

      if (previousIcon) {
        targetButton.removeChild(previousIcon);
      }

      targetButton.appendChild(favorButtonIcon);
    }
  }

  async saveOrRemoveFavouriteToBackend(
    favourButton,
    targetFavouriteKey,
    removeParameter = false
  ) {
    if (
      targetFavouriteKey &&
      targetFavouriteKey.classList.contains("param_list_keys")
    ) {
      const locationInfoButton = targetFavouriteKey.querySelector(
        ".location_info"
      );

      let fullParameterName = null;
      if (locationInfoButton) {
        fullParameterName = locationInfoButton.getAttribute(
          "fullparametername"
        );
      }

      if (fullParameterName) {
        const saveOrRemove = removeParameter ? "remove" : "save";

        const res = await this.utility.performFetchRequestWithBody(
          "/cjw/config-processing/parameter_list/" +
            saveOrRemove +
            "/favourites",
          "POST",
          [fullParameterName]
        );

        if (res) {
          this.provideSmallVisualFeedback(favourButton, res.status);

          if (res.status !== 200) {
            this.setOrRemoveFavourite(favourButton, targetFavouriteKey);
            this.switchFavourButtonModel(favourButton, targetFavouriteKey);
          } else if (this.dedicatedFavouriteView) {
            targetFavouriteKey.style.border = "1px solid red";
            this.removeFavouriteEntry(targetFavouriteKey);
          }
        }
      }
    }
  }

  provideSmallVisualFeedback(favourButton, statusCode = 200) {
    if (statusCode && statusCode > -1 && favourButton) {
      const favourSVG = favourButton.querySelector("svg");

      if (favourSVG) {
        if (statusCode === 200) {
          favourSVG.style.fill = "green";
        } else {
          favourSVG.style.fill = "red";
        }

        setTimeout(() => {
          favourSVG.style.fill = "";
        }, 3000);
      }
    }
  }

  removeFavouriteEntry(parameterToRemove) {
    if (parameterToRemove) {
      const parent = parameterToRemove.classList.contains("param_list_items")
        ? parameterToRemove
        : parameterToRemove.parentElement;
      const parentCarrier = parent.parentElement;
      parentCarrier.removeChild(parent);

      if (
        !parentCarrier.classList.contains("param_list") &&
        parentCarrier.children.length === 1 &&
        parentCarrier.children[0].classList.contains("param_list_keys")
      ) {
        this.removeFavouriteEntry(parentCarrier);
      }
    }
  }
}
