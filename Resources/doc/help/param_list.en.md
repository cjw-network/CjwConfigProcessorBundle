# Help Pages: Complete Parameterlist (All Parameters)

This file is supposed to provide a detailed look at the 
`Parameterlist: All Parameters` - view.

## Purpose of the view

This view is responsible for displaying the entirety of the processed parameters
of your Symfony server.

The view provides every parameter which is known to the Symfony application and 
displays them without any limitations. That means that there is no site access filter 
applied to this assortment of parameters and every bit of configuration can be
found here (as long as it arrived in your Symfony application).   

## Ways to get parameters listed in this view

Since this view is supposed to show every single parameter it found
when processing the internal configuration, the only thing one has to do in
order to get their parameter featured in the list, is to declare the desired
parameter in their own configuration.

## Elements of the parameter view

There are a few recurring elements which can be found in almost every 
view throughout the frontend the bundle provides:

1. The header:

    The header is based on and derived from Ibexa / eZ Platform's headers. This means,
    that it should be looking familiar for the user, since it stems from the backoffice of your
    platform installation. Despite that, it features a few elements "unique" to the bundle:
    
    * `Last Update` - The last update part of the header informs you about when the parameters
    of your view have last been updated. Via this information, you can see whether your last
    changes have arrived in the view with your last refreshing of the page or not
    
        > Should the update time not reflect the last time you have updated your configuration,
        try refreshing the page.
    
    * `Help Button` - The help button appears in the form of a questionmark in the top right corner
     of the header. Once it is clicked, a help overlay will appear, displaying information
     from the various dedicated help files for the views.  

2. The left sidebar:

    The left sidebar menu will appear in every view of the bundle's frontend. As such it 
    features controls (buttons) which will switch to the desired view once they are being
    clicked.
    
    * `Parameter List: Site Access` - This button will redirect you to the main view of the
    bundle: the site access dependent view. There you will be able to filter parameters for
    site accesses and so on.
    
    * `Parameter List` - This button will lead you to the parameter view this help file is for:
    The parameter view free of limitations, displaying every parameter known to the application.
    
    * `Parameter List: Favourites` - This control leads you to the dedicated favourites view, in
    which (should the feature be activated, (for more on that, see the documentation for that,) 
    all parameters which have been marked as favourites will be displayed)
    
3. The main view:
    The main view of this parameterlist begins directly under the header. It features
    a sort of own header, sitting on top of the actual parameterlist and a few additional
    controls too:
    
    * The `Searchbar` - The searchbar sits in the middle of the list-header. Via the
    searchbar, it is possible to search for specific key-values and additionally even
    values of parameter values.
    
        * `Searchfield` - The heart of the searchbar is the text-input itself. 
        **To begin searching**, simply start typing. After a few moments, the first 
        results (or lack thereof) should be displayed.
        
        * `Clear input` - Once you begin typing, you should start seeing a big `X` around the right corner
        of the searchbar. If you click it, then the searchbar will be cleared and your former
        input removed.
        
        * `Switch Mode` - At the very end of the searchbar, another button should be displayed.
        It looks like three vertical sliders next to each other with different values. If you
        click on that button or press `Alt + M` (**Alt**er **M**ode), the search mode will switch
        from (default) searching for keys / key segments of parameters to searching for specific values
        of parameters.
            
            > When the searchbar is in **key search mode**, the bar will have a big blue outline around it,
            > when the searchbar is in **value search mode**, the bar will have a big green outline.
        
            * **You can also limit the search to a certain subtree!** To do that, simply
            write out the segments of your search like this:
            
            `rootKey` + `.` + `nextLevel` (`.nextLevel`), this pattern can be continued as much as you like,
            but make sure, that the level you enter into the search, actually exist under the 
            keys you write.
            
            ** This does not work with values!**
