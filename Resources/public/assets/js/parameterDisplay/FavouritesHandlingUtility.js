class FavouritesHandlingUtility {
  inlineFavouriteContainer;
  dedicatedFavouriteViewContainer;

  constructor() {
    this.dedicatedFavouriteViewContainer = document.querySelector(
      "[list=favourites]"
    );

    if (!this.dedicatedFavouriteViewContainer) {
      this.inlineFavouriteContainer = document.querySelectorAll(
        ".favourite_container"
      );
    }
  }

  setUpFavourites() {
    if (this.dedicatedFavouriteViewContainer) {
      const allFavourButtons = this.dedicatedFavouriteViewContainer.querySelectorAll(
        ".favour_parameter"
      );

      for (const favourButton of allFavourButtons) {
        favourButton.click();
      }
    } else if (this.inlineFavouriteContainer) {
      for (const container of this.inlineFavouriteContainer) {
        const topNodesOfFavourites = container.querySelectorAll(".top_nodes");

        for (const topNode of topNodesOfFavourites) {
          topNode.parentElement.style.marginLeft = "0";
          topNode.parentElement.classList.remove("dont_display");
        }

        this.markNodesAsFavourites(container);
      }
    }
  }

  markNodesAsFavourites(container) {
    const favouriteKeys = container.querySelectorAll(".param_list_keys");

    for (const key of favouriteKeys) {
      key.classList.add("favourite_key_entry");

      const locationInfoButton = key.querySelector(".location_info");

      if (locationInfoButton) {
        const counterPartLocationInfo = document.querySelectorAll(
          "[fullparametername='" +
            locationInfoButton.getAttribute("fullparametername") +
            "']"
        );

        this.markCorrespondingKeysAsFavourites(counterPartLocationInfo);
      }
    }
  }

  markCorrespondingKeysAsFavourites(locationInfoButtonList) {
    if (locationInfoButtonList) {
      for (const locationInfoButton of locationInfoButtonList) {
        const keyParent = locationInfoButton.parentElement;
        const favorButtonOfKey = keyParent.querySelector(".favour_parameter");

        if (favorButtonOfKey) {
          favorButtonOfKey.click();
        }
      }
    }
  }

  setUpSiteAccessSwitching() {
    const switcher = document.querySelector(
      "#favourites_site_access_selection"
    );

    if (switcher) {
      switcher.onchange = (event) => {
        event.preventDefault();
        event.stopPropagation();

        this.swapSiteAccessView(switcher.value);
      };
    }
  }

  swapSiteAccessView(siteAccess) {
    if (siteAccess && siteAccess.trim().length > 0) {
      if (siteAccess === "- no.siteaccess -") {
        window.location =
          "/admin/cjw/config-processing/parameter_list/favourites";
      } else {
        window.location = `/admin/cjw/config-processing/parameter_list/favourites/${siteAccess}`;
      }
    }
  }
}
