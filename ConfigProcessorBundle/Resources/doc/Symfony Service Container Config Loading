# Symfony 5 Config Loading Components

# The Call Order

## Kernel

The entire loading process starts with the kernel, and it's methods `boot()`, `initializeContainer()` and `buildContainer()`. Prior to initializing the container,
the bundles have been initialized through the `initializeBundles()` method and they are already present before the loading process begins.

Through the following method all of the loaders that take part in the loading of the config, are initialized:

```php
  /**
     * Returns a loader for the container.
     *
     * @return DelegatingLoader The loader
     */
    protected function getContainerLoader(ContainerInterface $container)
    {
        $locator = new FileLocator($this);
        $resolver = new LoaderResolver([
            new XmlFileLoader($container, $locator),
            new YamlFileLoader($container, $locator),
            new IniFileLoader($container, $locator),
            new PhpFileLoader($container, $locator),
            new GlobFileLoader($container, $locator),
            new DirectoryLoader($container, $locator),
            new ClosureLoader($container),
        ]);

        return new DelegatingLoader($resolver);
    }
```

--------------------------------------------------------------------------------

It is also the container that provides all directories in which to search for configuration files during the 
initialisation of the kernel and the service container:

```php
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->addResource(new FileResource($this->getProjectDir().'/config/bundles.php'));
        $container->setParameter('container.dumper.inline_class_loader', true);
        $confDir = $this->getProjectDir().'/config';
        // Added due to needing a new Tab-Group
        $container->addCompilerPass(new CustomTabGroupPass());

        $loader->load($confDir.'/{packages}/*'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{packages}/overrides/**/*'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{packages}/'.$this->environment.'/**/*'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}_'.$this->environment.self::CONFIG_EXTS, 'glob');
    }

    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $confDir = $this->getProjectDir().'/config';

        $routes->import($confDir.'/{routes}/'.$this->environment.'/**/*'.self::CONFIG_EXTS, '/', 'glob');
        $routes->import($confDir.'/{routes}/*'.self::CONFIG_EXTS, '/', 'glob');
        $routes->import($confDir.'/{routes}'.self::CONFIG_EXTS, '/', 'glob');
    }
```

--------------------------------------------------------------------------------

Now a smaller sample of a function called to produce and fill the service container:

**buildContainer():**

1. `foreach (['cache' => $this->warmupDir ?: $this->getCacheDir(), 'logs' => $this->getLogDir()] as $name => $dir)`
This line means the following:
    > * "cache" is the key for the result of the next line in the array that is being built
    > * "$this->warmupDir ?" either the variable is set and put as the value to the key "cache" or
        `$this->getCacheDir()` is called in order to get the directory for the key
    >
    > * The same happens with the "logs" word, which is the key that is set for the result of
    > * the `$this->getLogDir()` method
    1. If the resulting directory is not a directory (yet)
        1. If the directory could not be created by calling `@mkdir($dir, 0777, true)` and it is still 
        not a directory, **throw a new error**
    2. Else if it is a directory, but it is not writeable by the application, **throw another error**
2. The following functions are called in order to set up and start up the container:
    ```php
    $container = $this->getContainerBuilder();
    $container->addObjectResource($this);
    $this->prepareContainer($container);
    ```
3. Then, if the result of (the soon following line) creating the container and resolving the config file is not null, the result of the following
function call is merged into the actual `$container`: `if (null !== $cont = $this->registerContainerConfiguration
($this->getContainerLoader($container)))`
4. Then the following line is executed, which adds a compiler pass (a function or series of functions which
executes operations on the contents of the `$container` (in this case look for classes to cache)):
    ```php
    $container->addCompilerPass(new AddAnnotatedClassesToCachePass($this));
    ```
5. And finally the `$container` **is returned**
        
## DelegatingLoader

### Methods:

**Start point (Constructor):**

At construction of the object a **LoaderResolverInterface** is given and set as the internal resolver.

--------------------------------------------------------------------------------

**supports($resource, string $type = null):**

> * Question whether the internal loaders support the given file format <br>
> * A general check whether the file format is being supported accross the entirety of the symfony loaders. 

* Takes the resolver and its method **resolve($resource, $type)**
* The resolver then checks with all its internal registered loaders whether one is compatible
    * Depending on the result of the check boolean true or false is given back

--------------------------------------------------------------------------------

**load($resource, string $type = null):**

> Tries to load the given resource and uses the internal loader resources to achieve this

* First resolvers **resolve($resource, $type)** method is used and the result is set to the internal **loader** variable
* If no loader supports the given format, an exception is thrown
* In any other case the compatible loader is used
    * Returns the result of the loading process
    
## LoaderResolver

### Attributes

**loaders = []**

* This array contains all available registered loaders of the resolver

### Methods

**Start point (Constructor):**

* Is given an array of loaders (who implement the LoaderInterface) and adds these into the internal loader array
* The loaders are being added through the **addLoader(LoaderInterface $loader)** method

--------------------------------------------------------------------------------

**resolve($resource, $type):**

> Supposed to get the loader, of the ones registered to the resolver, that is capable of loading the given
> resource 

* Loops through all know loaders and checks whether one of them supports the given format
* This check is conducted through the use of the **supports($resource, $type)** method of the loader
* As soon as a loader returns a positive result, the loader is given back as the result

--------------------------------------------------------------------------------

**addLoader(LoaderInterface $loader):**

> Adds a given loader to the internal loader array

* The method takes the given loader and just pushes it onto the loaders array
* At the same time the resolver sets itself as the resolver for the loader

--------------------------------------------------------------------------------

**getLoaders():**

> Returns all registered loaders

* The method takes the loaders array and returns it without any change

## YamlFileLoader

### Attributes

**static array serviceKeywords:**

* An internal array which contains keywords regarding services
=> Meaning bundle Bundle functions or classes (?) <=
as a key value pair

**static array prototypeKeywords:**

* An internal array which contains keywords for prototypes (?) as key value pairs

**static array instanceofKeywords:**

* An internal array as well
* This includes keywords for instanceof (?) as key value pairs

**static array defaultsKeywords:**

* An internal array which includes default keywords (?) as key value pairs

#### Annotation regarding the arrays

Even though these variables represent four distinct arrays all of them contain 
a lot of the same values (for example: "bind", "autowire" and "tags"). Even still
the arrays progressively decrease in length.

**yamlParser:**

* Contains an instance of a YamlParser which is supposed to parse the yaml files

**anonymousServicesCount:**

* Seemingly a counter stating how many services without an ID have been registered
(more precisely with a given Tag of "service") 

**anonymousServicesSuffix:**

* This suffix is being appended to the anonymous services and is being generated through 
a hashing process by the ContainerBuilder

#### Annotation regarding the anonymous services

Symfony defines these services as services without a given ID. At the same time these
services cannot serve as dependency (injection) for other services and they will only be
instantiated when they are actively being used.

**autoRegisterForSinglyImplementedInterfaces:**

* This is a boolean which seemingly describes exactly what it says in the title

#### Annotation regarding all attributes (also of this class)

Since symfony lacks any sort of documentation / comments on these variables, and most of the methods
as well, there are no guarantees for any of the values and types of values stored within them
or the purpose of these attributes. All the given descriptions and explanations are being constructed through
third party reviews and speculations (by me).

### Methods

**Starting point (Constructor):** 

* The constructor of the class is being supplied by the FileLoader abstract class, which takes a FileLocator
as an argument

--------------------------------------------------------------------------------

**load($resource, string $type = null):**

> As in all loaders (including the DelegatingLoader) this method is meant to load the given resources and process it

1. First the given resource is being located by the FileLocator and its **path** is set as a result
2. Next a call to the **loadFile($file)** function inside the same class is being made in order to get 
the content of the file
3. Afterwards there is a check through the service container whether the file of the given **path** exists
    * Should the content (result) of the loadFile method be empty, the function will be stopped
4. After that check another call is being made to a function of the same class **parseImports(array $content, string $file)**
    * That function supposedly serves to identify the files being imported by the current Yaml-file and serves to import them as well
5. Then the received content is filtered for the parameters of the file, and the execution is stopped, when the parameters aren't an
array
    * If the parameters are shipped as an array then the class internal function **resolveServices($value, string $file, bool $isParameter = false)**
    will be employed to (probably) get the services out of the parameter, and the parameter key as well as the service will then be set
    as parameters of the service container through its **setParameter(string $name, $value)** method 
    * If there are no parameters in the content, execution will continue without parameters
6. Next the loaded content (in the form of an array) is given to the classes internal **loadFromExtensions(array $content)** function,
which seems to take the **namespace** of the parameter (possibly its identifier / name) and its set **value** and uses the containers function
of the same name to again load from extension.
7. The parameters for the anonymous services are being set (the suffix via hash, the counter to 0) and the current directory is set to that
of the file that is being loaded
8. Now the class internal function **parseDefinitions(array $content, string $file)** is called to (maybe) produce service definitions
9. Finally, the instanceof variable is being reset or initialised and then the class internal function **registerAliasesForSinglyImplementedInterfaces()** is called  

--------------------------------------------------------------------------------

**supports($resource, string $type = null):**

> As before, this method is mainly responsible for determining compatibility between the loader, and the resource to load

* Takes the given resource string and checks whether it is a string 
    * (if not => return false), 
* then it is being checked, whether the type is null, and the resource's file extension is part of the supported extension array (yaml and yml)
    * if so, true is returned
    * else if the type is not null, 
        * it is being checked whether it contains the extension yaml or yml and that is being returned

--------------------------------------------------------------------------------

**parseImports(array $content, string $file):**

> This function is responsible for getting all the yaml files the current file imports

* First a check whether there have been imports in the file
    * If not, stop the function execution
* Then check whether the imports come in the form of an array
    * If not, throw a new exception `("The "imports" key should contain an array")`
* Then the directory of the current file is determined and 
* For every import:
    * Every import is being checked for being an array or not
        * If it is not, it will be set to a new array 
        * Else the code is being continued
    * Then there is a check if the "resource" key is set in the array
        * If not, an error is thrown
        * Else the code continues
    * Finally the current directory is being set to that of the file that is being parsed / loaded
    * And then the imports are being imported one by one

--------------------------------------------------------------------------------

**parseDefinitions(array $content, string $file):**

> This function is supposed to parse "definitions", which might mean Service-Definitions (whether in the yaml a service is being set).
> While it is not entirely certain to what capacity this is done, it at least takes the service names and some other info and uses them internally.

1. First the content of the file is being checked for whether it contains information regarding services
    1. If not, function execution is being stopped
2. If the services key is present in the content array, then it is being checked, whether an array is contained under that key
    1. If not, then an error is thrown
3. Then it is being checked whether the service array contains the `_instanceof` key or not 
    1. If not, nothing happens
    2. If it does, then it is being checked whether the instanceof is an array
        1. If not, an error is thrown
    3. The internal instanceof is being initialised with an empty array and then `isLoadingInstanceof` is being set to true
    4. `foreach ($instanceof as $id => $service)` 
        1. If the service is neither set nor an array, then an error is thrown
        2. If the service is a string and an `@` is contained in the service string on position 0, another exception is thrown
        3. The class internal function **parseDefinition(string $id, $service, string $file, array $defaults, bool $return = false)** is called 
        with the id of the service, the service array and the file path (and an empty array)
        > Notice that there is one s less at the end of the function-that-is-being-called's name
4. `isLoadingInstanceof` is set to false again
5. A `defaults` variable is given the result of the **parseDefaults(array &$content, string $file)** function as value
6. Once more for each `$content['services'] as $id => $service` the **parseDefinition(string $id, $service, string $file, array $defaults, bool $return = false)** 
function is called, but this time with the `defaults` variable instead of an empty array 

--------------------------------------------------------------------------------

**parseDefaults(array &$content, string $file): array:**

> Takes values belonging to the services stored under the `_defaults` key and parses them or uses them further internally

1. Does the `_defaults` key exist in the service array of the content
    1. If not, then just return an empty array (since there are no default values)
2. If the defaults exist, they must be an array, so check for that
    1. If they are not present as an array then throw a new exception
3. `foreach ($defaults as $key => $default)` it is being checked whether the key exists in the internal
variable array `$defaultsKeywords`
    1. If not, then an error is thrown
4. If the default array contains the key `tags`
    1. If the contents of the array under the tags key do not come as an array
        1. Throw an error
    2. For every tag
        1. If that tag does not come in array form, make it an array
        2. If the current tag only contains 1 item and the current tag element is an array
            1. Set the tag `name` to the key of the tag, and the actual `tag` to the current element from the current tag
            2. If all of that is not the case, check whether there is a value set under `name` key in the current tag array
                1. If not, then throw an error
                2. If there is, set the `name` variable to that value
        3. If the `name` is not a string or empty
            1. throw a new exception
        4. `foreach ($tag as $attribute => $value)` 
            1. Check for whether the value of the tag is not [scalar](https://www.php.net/manual/en/function.is-scalar.php) and it is null
            and if those conditions are met, then an error is thrown
5. Check for whether the defaults contain a `bind` key
    1. If so, if the value, the key is pointing to, is not an array, then an error is thrown
    2. If it is an array, code will be continued and `foreach ($this->resolveServices($defaults['bind'], $file) as $argument => $value)`
    is performed, wherein the class internal **resolveServices($value, string $file, bool $isParameter = false)** is called
        1. the internal defaults array receives a new `BoundArgument` with the result value and a few options under the keys `bind` and `$argument`
6. The defaults array is returned

--------------------------------------------------------------------------------

**isUsingShortSyntax(array $service): bool:**

> This function is used to determine, whether the services, given to the function, employ a short syntax or not

1. `foreach ($service as $key => $value)`
    1. If the key is a string, **and** the key is empty **or** does not start with a `$` **and** does not contain `\\`
        1. return false

2. return true

--------------------------------------------------------------------------------

**parseDefinition(string $id, $service, string $file, array $defaults, bool $return = false):**

> This function serves to parse the definition of one service (that is given to it) and all the parameters that are attached to
> that service definition. 

This function is over 350 lines long and features countless if-statements regarding specific keys appearing in any of the
parameters and definitions of the service. It also checks for the naming conventions of the services, where it does not allow
certain characters or words to appear in the service name and its parameters. Due to its long nature, a more brief overview and
less detailed description is given.

1. If the service starts with an `@`, a new alias is created for it and then placed within the service container,
afterwards that alias is returned, **and the function execution ends**
2. If the service is both an array and using the short syntax, it is being placed into an associative array `$service = ['arguments' => $service];`
3. If the `service` variable is null, it is transformed into an empty array
4. Is there something set under the `stack` key in the service array and is it an array as well
    1. **If not, an error is thrown**
    2. `foreach ($service['stack'] as $k => $frame`
        1. Is that frame both an array (containing only one element) and does not have a key overlapping with the keys of the `serviceKeywords`,
        repackage it:
            ```php
              $frame = [
                'class' => key($frame),
                'arguments' => current($frame),
              ];
            ```
        2. Is the frame both an array and contains a `stack` key, throw an error
        3. Set the `defintion` variable to the result of this class internal call: 
            ```php 
            $definition = $this->parseDefinition($id.'" at index "'.$k, $frame, $file, $defaults, true);
           
            if ($definition instanceof Definition) {
                $definition->setInstanceofConditionals($this->instanceof);
            }
           
            $stack[$k] = $definition;
            ```
       4. With this definition information, the definition is put into the stack under the frame key
    3. Now a check occurs for whether the given `service` array only contains the keys `['stack', 'public', 'deprecated']` **and if not, an error is thrown**
    4. Now the service variable receives new values:
    ```php
     $service = [
       'parent' => '',
       'arguments' => $stack,
       'tags' => ['container.stack'],
       'public' => $service['public'] ?? null,
       'deprecated' => $service['deprecated'] ?? null,
     ];
    ```
5. The class internal **checkDefinition(string $id, array $definition, string $file)** is called

    > A seemingly specific alias section follows

6. If the service already contains an alias (`$service["alias"]` is set) create a new alias for the service
with this information and set it public or private, depending on the public property set for the alias in the service
    1. Check the same for the alias as for the service in step 4.3
    2. Check whether the key of the alias contains `deprecated` and trigger a deprecation warning if it does
    3. Again `return $return ? $alias : $this->container->setAlias($id, $alias);`

    > The alias processing seems to be done at that point

7. Since this function sometimes is called by **parseDefinitions**, `isLoadingInstanceOf` might be set to true
and 
    1. if it is, then the `definition` variable is initiated as a `ChildDefinition` without a parent, which means,
    that this is a definition that extends another definition
    2. or if `isLoadingInstanceOf` is false then if the `$service["parent"]` is set
        1. if the parent is not an empty string **and** the first element under the parent key starts with `@`
        then an **error is thrown**
        2. Otherwise, a new `ChildDefinition` with the content of `$service["parent"]` will be given to `$definition`
    3. In any other case a simple "normal" `Definition` is created

    > On a site note the definition seems to be the result of the parsing of the service's definition, meaning it holds
    > the service definition as (also) noted by the inner documentation of the class

8. Countless smaller options follow, where everytime it is checked whether the key is set in the `service` array
and if it is, the option is set to the value present under that key through a function of the `Definition` object. It
continues until the `calls` keyword;
    1. A special case in that range is the deprecated key, which, when set, triggers a deprecation warning with certain options
    > The call specific section begins
9. When the `calls` keyword is reached, it is being checked whether something is set under that key and only if that is the case, the code
continues, otherwise an **error is thrown**
    1. `foreach ($service['calls'] as $k => $call)`
        1. If the key `$k` is not a string **and** the value beneath the `call` key is neither an array nor a TaggedValue, **an error is thrown**,
        also if the `$k` key is a string, then also **throw an error**
        2. If `$call['method']` is set and has a value, a few variables are set:
            ```php
                $method = $call['method'];
                $args = $call['arguments'] ?? [];
                $returnsClone = $call['returns_clone'] ?? false;
            ```
        3. Otherwise, if there is just one item as content of the `$call` variable, and the key of this variable is a string
            ```php
               // Then a few variables are set through the content
               $method = key($call);
               $args = $call[$method];
           
               // If the arguments of the method is a TaggedValue
               if ($args instanceof TaggedValue) {
                   // If the arguments come with a tag other than "returns_clone", throw an error
                   if ('returns_clone' !== $args->getTag()) {
                       throw new InvalidArgumentException(sprintf('Unsupported tag "!%s", did you mean "!returns_clone" for service "%s" in "%s"?', $args->getTag(), $id, $file));
                   }
                   
                   // In any other case, the returnsClone is set to true, since then the method returns a "clone"?
                   $returnsClone = true;
                   $args = $args->getValue();
               } else {
                   // If the arguments are not of type TaggedValue, 
                   $returnsClone = false;
               }
            ```
       4. If the `$call` variable does not contain anything as its first item (if `$call[0]` is empty), then a **new exception is thrown**
       5. In any other case the variables are set the following way:
       ```php
            $method = $call[0];
            $args = $call[1] ?? [];
            $returnsClone = $call[2] ?? false; 
       ```
        > The call specific section ends
    2. If the arguments do not come in the form of an array: **throw an error**
    3. The `$args` variable is then set to the result of the internal function call **$this->resolveServices($args, $file);**
    4. The `$definition` variable is used for a class external function call **$definition->addMethodCall($method, $args, $returnsClone);**
    > The tags section begins
10. The `tags` key is checked for both the `$service` and `$defaults` arrays and if something is set under that key, the content will
be added to the `$tags` variable (which is an array), but if the `$service["tags"]` does not represent an array, **an error is thrown**
11. The tags are looped through: `foreach ($tags as $tag)`
    1. If the `$tag` isn't an array, it is made one
    2. If there is only one element in the `$tag` and that element is also an array, the variables are set based on that
    `$name = key($tag); $tag = current($tag);`
    3. If not, the `name` key is checked from the `$tag` array and if it is not set, **an error is thrown**,
    but if it is set, then the `$name` variable receives the value stored in `$tag["name"]`
    4. If the `$name` does not contain a string or that string is empty, then **an error is thrown**
    5. `foreach ($tag as $attribute => $value)`
        1. If the `$value` is not a scalar value and not null, **an error is thrown**
    6. Another class external function call occurs **$definition->addTag($name, $tag);**, which probably adds that tag to the service definition object
    > With that, the tag specific section of the function comes to a close
12. `if (null !== $decorates = $service['decorates'] ?? null)` means, that it is a check for whether something is set in `$service["decorates"]`
and that value is also given to the `$decorates` variable (or null)
    1. If the `$decorates` is not empty but also starts with `@`, **an error is thrown**
    2. The `$decorationOnInvalid` variable is set as the result of a check for whether the key `decoration_on_invalid` exists as a key in the `$service`
    array as either the content of that part of the array or the string `"exception"`
    3. based on the value of `$decorationOnInvalid` either the `$invalidBehavior` variable is set, or an **exception is thrown**
        1. The allowed values are either the string `exception`, where on "invalid behavior" an exception is thrown, the string
        `ignore`, where the invalid behavior is ignored and `null` on which something is then set to null
        2. Both the string `null` and any other word or value will trigger **an exception**
        > The invalidBehavior variable probably states what is supposed to happen, when something unexpected happens or
        > something is done wrong by the service
    4.  next the following values are also set
        ```php
             $renameId = isset($service['decoration_inner_name']) ? $service['decoration_inner_name'] : null;
             $priority = isset($service['decoration_priority']) ? $service['decoration_priority'] : 0;
        ```
    5. At last, another class external function call is made in order to: `Set the service that this service is decorating.`
    in the form of **$definition->setDecoratedService($decorates, $renameId, $priority, $invalidBehavior);**
13. It is checked whether `autowire` is set as a key in the `$service` and following that, the value (if it is set) is given to the
definition
    > A "binding" specific section follows
14. First it is checked whether the `bind` key is set either the `$service` or the `$defaults`
    1. If there is, first this is done:
        ```php
           // deep clone, to avoid multiple processing of the same instance in the passes
           $bindings = isset($defaults['bind']) ? unserialize(serialize($defaults['bind'])) : [];
        ```
    2. If then the `bind` key is also set in `$services`
        1. The content is not allowed to be an array because otherwise **an error is thrown**
        2. The `$bindings` variable is extended through the result of a class internal function call **$this->resolveServices($service['bind'], $file)**
        3. Then the `$bindingType` is set as either `BoundArgument::INSTANCEOF_BINDING` or `BoundArgument::SERVICE_BINDING` depending on whether `$this->isLoadingInstanceOf`
        is true or not
        4. `foreach ($bindings as $argument => $value)`
            1. If the value is not a `BoundArgument`, then in `$bindings[$argument]` a `new BoundArgument($value, true, $bindingType, $file);` is created and 
            inserted based upon the actual value, and the bindType 
    3. Through a class external function call: **$definition->setBindings($bindings);**, the binding is given to the service
15. A check regarding the `autoconfigure` key is made for whether a value is set for it and depending on the outcome, the value is set to the service
16. Then it is checked, whether the `namespace` key exists but the `resource` key doesn't because in that case **an error is thrown**
17. **The same occurs** when `$return` is set **and** the `resource` key is as well, but if only `$return` is set, then the `$definition` **is returned**,
and the function execution ends
18. Lastly it is being checked again, whether the `resource` key exists in the `$service`
    1. If the content under that key is not a string, then **an error is thrown**
    2. Then a bunch of variables is set:
        ```php
           $exclude = isset($service['exclude']) ? $service['exclude'] : null;
           $namespace = isset($service['namespace']) ? $service['namespace'] : $id;
        ```
    3. And a class internal function call is made in the form of **$this->registerClasses($definition, $namespace, $service['resource'], $exclude);**
19. If the `resource` key does not exist, then another class external function call is made to **complete the function execution**
in the form of: **$this->setDefinition($id, $definition);**

--------------------------------------------------------------------------------

**parseCallable($callable, string $parameter, string $id, string $file):**

> It is this functions intent to parse callable functions which are given alongside the service definition
> and are supposed to be executed once the service has been initialised

1. Check if the given `$callable` is a string
    1. Check if the string is not empty **and** its first character is an `@`
        1. Check if there is no `:` present in the string
            1. **return** a class internal function call as array [**$this->resolveServices($callable, $file), '__invoke'**]
        2. **Throw a new exception**
    2. **return** `$callable`
2. Check if the given `$callable` is an array
    1. Check if `$callable`s first and second item are set
        1. **return** an internal function call as an array **[$this->resolveServices($callable[0], $file), $callable[1]]**
    2. Check if `$parameter` equals the string: `factory` **and** `$callable[1]` is set **and** `callable[0]` equals null,
    if true, **return** `$callable`
    3. **Throw a new exception**
3. **Throw an exception**

--------------------------------------------------------------------------------

**loadFile($file):**

> This function is supposed to actually load a Yaml / Yml file by also parsing it and returning its content

1. At first a check occurs for whether the `Symfony\Component\Yaml\Parser` class exists in the project
    1. If not, then **an error is thrown**
2. Secondly it is checked, whether path given to the function leads to a local file or an external resource
    1. If it isn't a local file, **throw an exception**
3. Thirdly a check occurs for whether the path actually points to a file
    1. Otherwise, **an error is thrown**
4. Fourthly, if the `$this->yamlParser` variable has not been initialised (set to some value other than null)
    1. Initialise the variable with a `new YamlParser()` instance
5. Now a class external function call is made **$this->yamlParser->parseFile($file, Yaml::PARSE_CONSTANT | Yaml::PARSE_CUSTOM_TAGS);**,
which is supposed to parse the given file and then return its contents into the `$configuration` variable
    1. If anything goes wrong while parsing the file, **an error is thrown**, which states that the file did not contain valid YAML
6. Lastly another class internal function call is made **$this->validate($configuration, $file);** and **returned**, ending function execution

--------------------------------------------------------------------------------

**validate($content, string $file): ?array:**

> This validates a YAML file (as stated by Symfony's own internal documentation), and I can thus only assume, that 
> the function checks whether the parsed information conforms to the yaml standard

1. It is checked whether the `$content` given to the function is null or not
    1. If it is, then the `$content` **is returned**
2. Then, if the `$content` is not an array, **an error is thrown**
3. `foreach ($content as $namespace => $data)`
    1. If the specific keywords `imports`,`parameters` or `services` appear as the `$namespace`, then _continue_
    2. else, the loop is continued and if the class external function call **$this->container->hasExtension($namespace)** returns false
        1. First all extensions that are registered to the `$this->container` are mapped via their alias into an array and then that
        array is filtered without a criterion and passed on to the `$extensionNamespaces` variable
            ```php
               // The line looks like this
               $extensionNamespaces = array_filter(array_map(function (ExtensionInterface $ext) { return $ext->getAlias(); }, $this->container->getExtensions()));
            ```
        2. Immediately after, **an error is thrown** stating, that "there was no extension able to load the configuration"
4. Lastly the function simply **returns** the `$content`

--------------------------------------------------------------------------------

**resolveServices($value, string $file, bool $isParameter = false):**

> It is not very clear what this function does, but as it stands it is a function that takes values and
> does something with them while at the same time calling itself multiple times. 
> It "Resolves services" - internal Symfony documentation

1. If the given `$value` is an instanceof a `TaggedValue`
    1. `$argument` is given the value of the `$value->getValue()`
    2. If the tag of the `$value->getTag()` equals the string `iterator`
        1. If the `$argument` is not an array, **an error is thrown**
        2. The `$argument` then becomes the result of the internal function call **$this->resolveServices($argument, $file, $isParameter);**
        3. It is tried to return a `new IteratorArgument($argument)`, which (the class) seems to be a list of values to iterate over
            1. If an error occurs while trying to create and return the new `IteratorArgument`, the error is caught, and a new one is thrown
            ,which states that '"!iterator" tag only accepts arrays of "@service" references'
    3. If the tag value instead equals the string `service_locator`
        1. The [1.2.1]() step is repeated with a different error message
        2. The [1.2.2]() step is repeated
        3. Now, similarly to the previous [1.2.3]() step, it is tried to return a `new ServiceLocatorArgument($argument)`
            1. If that fails (an error is thrown), it is caught and a new one is thrown, which states that: 
            '"!service_locator" tag only accepts maps of "@service" references'
    4. Should the `value->getTag()` now be either one of the strings `['tagged', 'tagged_iterator', 'tagged_locator']`
        1. The `$forLocator` is set to the result of the comparison between the string `tagged_locator` and the `$value->getTag()`
        2. If `$argument` is an array **and** the `tag` key is set in `$arguments` **and** `$argument["tag"]` (is not null or undefined or false)
            1. If there is a key in `$argument`, that is not included in the string list: `['tag', 'index_by', 'default_index_method', 'default_priority_method']`
            **throw an error** (which is supposed to state, that there has been used an unsupported key)
            2. `$argument` is now set to 
            ```php
               // This class is stated to: "Represent a collection of services found by tag name to lazily iterate over."
               new TaggedIteratorArgument($argument['tag'], $argument['index_by'] ?? null, $argument['default_index_method'] ?? null, $forLocator, $argument['default_priority_method'] ?? null);
           ```
        3. Else if `$argument` is a string **and** also true
            1. The `$argument` is set to: `new TaggedIteratorArgument($argument, null, null, $forLocator);`
        4. In any other case **an exception is thrown** which states, that: "tags only accept a non-empty-string, or an array with a key 'tag'"
        5. If the `$forLocator` is set (true), then the `$argument` is set to a new `ServiceLocatorArgument($argument)`
        6. **The `$argument` is returned**
    5. If `$value->getTag()` equals the string `service`
        > It is important to remember that, whenever a service contains the tag or name "service" it is an anonymous service
        1. If the given `$isParameter` boolean is true, **an error is thrown**, which states, that "an anonymous service can not be used in a parameter"
        2. a bunch of variables are then set:
            ```php
            $isLoadingInstanceof = $this->isLoadingInstanceof;
            $this->isLoadingInstanceof = false;
            $instanceof = $this->instanceof;
            $this->instanceof = [];
            ```
           as well as this:
           ```php
           $id = sprintf('.%d_%s', ++$this->anonymousServicesCount, preg_replace('/^.*\\\\/', '', isset($argument['class']) ? $argument['class'] : '').$this->anonymousServicesSuffix);
           ```
           which says, that the `$id` is going to be a string which contains the `anonymousServiceCount`, `_` and then either the `$argument['class']` 
           or nothing, plus `$this->anonymousServicesSuffix`
        3. Now a class internal function call is performed **$this->parseDefinition($id, $argument, $file, []);**
        4. If the container then does not have the $id as a definition (`!$this->container->hasDefinition($id)` returns false),
        **a new exception is thrown**, stating that "Creating an alias using the tag "!service" is not allowed"
        5. Then, since the service is anonymous, it is set to private (`$this->container->getDefinition($id)->setPublic(false);`)
        6. Then two more variables are set:
            ```php
            $this->isLoadingInstanceof = $isLoadingInstanceof;
            $this->instanceof = $instanceof;
            ```
        7. A `new Reference($id)` **is returned**, which is: (as stated by Symfony's internal documentation)
        "Reference represents a service reference."     
    6. If the `$value->getTag()` instead equals the string `abstract`, an `AbstractArgument($value->getTag())` **is returned**
    7. Finally, a new **exception is thrown** due to the tag "not being supported"
2. Then a check occurs for whether the `$value` is of type array
    1. `foreach ($value as $k => $v)`
        1. `$value[$k]` is then set to the result of `$this->resolveServices($v, $file, $isParameter);`
3. But if the `$value` is of type string and features an `@=` on index 0
    1. Check whether the `Expression` class exists in that context 
        1. If it doesn't, **a new exception is thrown**
    2. A new `Expression(substr($value, 2));` is then **returned**
4. If instead the `$value` is a string and does contain `@` at index 0
    1. It is checked, whether the `$value` also contains a second `@` right behind it (whether `@@` is contained at index 0)
        1. If so, variables are set the following way:
        ```php
        $value = substr($value, 1);
        $invalidBehavior = null;
        ```
    2. If instead a `!` follows directly behind, meaning that `$value` contains `@!` on index 0
        1. Variables are set the following way:
        ```php
        $value = substr($value, 2);
        $invalidBehavior = ContainerInterface::IGNORE_ON_UNINITIALIZED_REFERENCE;
        ``` 
    3. If instead a `?` follows directly behind, meaning that `$value` contains `@?` on index 0
        1. Variables are set the following way:
        ```php
        $value = substr($value, 2);
        $invalidBehavior = ContainerInterface::IGNORE_ON_INVALID_REFERENCE;
        ```
    4. In any other case
        1. The variables are set the following way:
        ```php
        $value = substr($value, 1);
        $invalidBehavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE;
        ```
    5. If the `$invalidBehavior` does not equal `null`
        1. `$value` is passed the following: `new Reference($value, $invalidBehavior);`
5. `$value` **is returned**
       
#### Annotation regarding the `@`+ `character` symbols

```yaml
# if the value of a string parameter starts with '@', you need to escape
# it by adding another '@' so Symfony doesn't consider it a service
# (this will be parsed as the string '@securepassword')
mailer_password: '@@securepassword'
```

and:

```yaml
# this is not a string, but a reference to a service called 'logger'
arguments: ['@logger']
```

--------------------------------------------------------------------------------

**loadFromExtensions(array $content):**

> Does, as the documentation describes, load [something] from extension.

1. `foreach ($content as $namespace => $values)`
    ```php
    /*
       This exact passage of code has already appeared before in the <validate>-function   
    */
      
    if (\in_array($namespace, ['imports', 'parameters', 'services'])) {
       continue;
    }
    ```
    1. A check for whether the `$values` are not an array **and** they are equal to `null`
        1. If successful, the `$values` are initialised as an empty array
    2. A class external function call is performed **$this->container->loadFromExtension($namespace, $values);**

--------------------------------------------------------------------------------

**checkDefinition(string $id, array $definition, string $file):**

> This function takes the previously created lists of keywords and checks whether there is any key of the given defintion
> which does not fall into one of these lists in order to make sure, that no unsupported keywords are used

1. First if `$this->loadingInstanceOf` is currently set to true, the `$keywords` variable is set to the `$instanceOfKeywords`
2. Or if that is not the case, if the `resource` **or** `namespace` keys are set in the `$definition`, then the `$keywords` 
are set to `$prototypeKeywords`
3. In any other case, the `$keywords` are set to `$serviceKeywords`
4. Then `foreach ($definition as $key => $value)`
    1. If the `$key` of the `$definition` is not set in the `$keywords`, **throw a new error**

## Definition the class and also basic Definition**s**

**Purpose**

According to [Symfony's own documentation](https://symfony.com/doc/current/service_container/definitions.html):

> Service definitions are the instructions describing how the container should build a service. They are not the actual services used by your applications. 
> The container will create the actual class instances based on the configuration in the definition.

According to a [medium article](https://medium.com/manomano-tech/diving-into-symfonys-dependencyinjection-part-1-first-steps-with-the-container-2fad0593c052)

> The Definition class serves the following purpose: "Definitions are a way to set how the container configures and instantiates services." and
> "We call the Definition::addArgument method in order to add arguments to the constructor of the AccessManager (custom class created in the article). Here the 
> passed argument is an array of References, which is an object that represents a service reference. A reference takes the service's ID as argument."

--------------------------------------------------------------------------------

**Alternative method to register / do definitions in the container**

Another note on the class by the same article:

> "Note that a more compact way to add definitions in the container exists using the register function. The Definition is a fluent class, it means you can chain the methods in 
> order to ease configuration."

and:

```php
<?php
// public/index.php

// the register function will create and return a Definition with 
// its first parameter as id, and the second as class
$containerBuilder->register('post_voter', PostVoter::class);
$containerBuilder->register('access_manager', AccessManager::class)
    ->addArgument([new Reference('post_voter')]);

$accessManager = $containerBuilder->get('access_manager');
```

--------------------------------------------------------------------------------

**Public or private definition**

And another note:

> We can set a definition to be public or not. A private definition is not be accessible from the ContainerBuilder::get() method, while a public definition can be.

--------------------------------------------------------------------------------

**Dependency Injection**

A note to the loading of configuration and creation of the services based on that from the same article:

> The DependencyInjection component will use the FQCN as the service's id, therefore instead of doing get('access_manager') to access the AccessManager,
> you have to do get(AccessManager::class).

Another information regaring autoloading:

> To auto-configure services in a given directory, we use the Loader::registerClasses() function.

```php
<?php
// ...
// prototype definition
$definition = new Definition();
$definition->setPublic(false);

// classes in the namespace App\ and the directory src/ will be register in the container
$loader->registerClasses($definition, 'App\\', '../src/*');

$container->getDefinition(AccessManager::class)
    ->setPublic(true)
    ->addArgument([new Reference(PostVoter::class)]);
```

> All classes in the src directory were automatically registered as services. However, we don't want the Entity directory to be autoconfigured. 
> You can exclude it by adding a third argument (the directory to exclude) to the registerClasses() function.

```php
// src/Entity directory is now excluded
$loader->registerClasses($definition, 'App\\', '../src/*', '../src/Entity');
```

--------------------------------------------------------------------------------

**Service Tags**

Information from the [Symfony Tag Documentation](https://symfony.com/doc/current/service_container/tags.html)

> Service tags are a way to tell Symfony or other third-party bundles that your service should be registered in some special way. Take the following example:

```yaml
# config/services.yaml
services:
    App\Twig\AppExtension:
        tags: ['twig.extension']

# Services tagged with the twig.extension tag are collected during the initialization of TwigBundle and added to Twig as extensions.
```

[List of tags](https://symfony.com/doc/current/reference/dic_tags.html) of the Symfony application

**_instanceof** belonging to tags

> If you enable autoconfigure, then some tags are automatically applied for you. 
> If you want to apply tags automatically for your own services, use the _instanceof option

```yaml
# config/services.yaml
services:
    # this config only applies to the services created by this file
    _instanceof:
        # services whose classes are instances of CustomInterface will be tagged automatically
        App\Security\CustomInterface:
            tags: ['app.custom_tag']
    # ...
```

> Reference Tagged Services Symfony provides a shortcut to inject all services tagged with a specific tag,
> which is a common need in some applications, so you don’t have to write a compiler pass just for that. 
>In the following example, all services tagged with app.handler are passed as first constructor argument to the App\HandlerCollection service

```yaml
# config/services.yaml
services:
    App\Handler\One:
        tags: ['app.handler']

    App\Handler\Two:
        tags: ['app.handler']

    App\HandlerCollection:
        # inject all services tagged with app.handler as first argument
        arguments:
            - !tagged_iterator app.handler
```

```php
// src/HandlerCollection.php
namespace App;

class HandlerCollection
{
    public function __construct(iterable $handlers)
    {
    }
}
```

The article mentions two important methods to work with services that have been tagged:

> * registerForAutoconfiguration(): it allows to configure all classes that implement a certain interface.
> * findTaggedServiceIds(): to fetch all tagged services

--------------------------------------------------------------------------------

**Method Calls**

According to [Symfony's definitions documentation](https://symfony.com/doc/2.3/components/dependency_injection/definitions.html)

> If the service, you are working with, uses setter injection then you can manipulate any method calls in the definitions as well.

Note by me:

> It seems to be the case that these method calls are, exactly as it is described, used to call specific methods of services with given
> arguments that are being or have been registered to the service container

```php
// To add a method call by:
$definition->addMethodCall($method, $arguments);
```

> Where $method is the method name and $arguments is an array of the arguments to call the method with. The arguments can be strings, arrays,
> parameters or service ids as with the constructor arguments.

```php
// More examples
// gets all configured method calls
$methodCalls = $definition->getMethodCalls();

// configures a new method call
$definition->addMethodCall('setLogger', [new Reference('logger')]);

// configures an immutable-setter
$definition->addMethodCall('withLogger', [new Reference('logger')], true);

// replaces all previously configured method calls with the passed array
$definition->setMethodCalls($methodCalls);
```

--------------------------------------------------------------------------------

**Files**

According to [Symfony's definitions documentation](https://symfony.com/doc/2.3/components/dependency_injection/definitions.html)
and the [Working with Container Service Definitions](https://symfony.com/doc/2.3/components/dependency_injection/definitions.html):

> There might be use cases when you need to include another file just before the service itself gets loaded. To do so, you can use the setFile() method:

```php
  $definition->setFile('/src/path/to/file/foo.php');
```  
  
> Notice that Symfony will internally call the PHP statement require_once, which means that your file will be included only once per request.

--------------------------------------------------------------------------------

**Arguments**

According to [Symfony's Service Container Documentation](https://symfony.com/doc/current/service_container.html)

> (Paraphrased) Arguments are parameters that will be used, when the service is being constructed (for example a 
> parameter that can't be autowired (if it is not another service) may be directly set as an argument in the config file
> and will then be given to the constructor of the service, when the service is being instantiated during the loading process)

Example:

```yaml
# explicitly configure the service
    App\Updates\SiteUpdateManager:
        arguments:
            $adminEmail: 'manager@example.com'

# Thanks to this, the container will pass manager@example.com to the $adminEmail argument of __construct when creating
# the SiteUpdateManager service. The other arguments will still be autowired.
```

--------------------------------------------------------------------------------

**Aliases**

As defined by the [Symfony Documentation](https://symfony.com/doc/current/service_container/alias_private.html#services-alias):

> You may sometimes want to use shortcuts to access some services. You can do so by aliasing them and, furthermore, 
>  you can even alias non-public services.
 
Example:

```yaml
# config/services.yaml
services:
    # ...
    App\Mail\PhpMailer:
        public: false

    app.mailer:
        alias: App\Mail\PhpMailer
        public: true
```

> This means that when using the container directly, you can access the PhpMailer service by asking for the app.mailer service like this:

```php
$container->get('app.mailer'); // Would return a PhpMailer instance
```

> In YAML, you can also use a shortcut to alias a service:

```yaml
# config/services.yaml
services:
    # ...
    app.mailer: '@App\Mail\PhpMailer'
```

--------------------------------------------------------------------------------

**Anonymous Services**

As defined by the [Symfony Documentation](https://symfony.com/doc/current/service_container/alias_private.html#services-alias):

> In some cases, you may want to prevent a service being used as a dependency of other services. 
> This can be achieved by creating an anonymous service. These services are like regular services, but they 
> don’t define an ID, and they are created where they are used.

--------------------------------------------------------------------------------

**Closure**

As defined by the [PHP Documentation](https://www.php.net/manual/de/class.closure.php):

> Class used to represent anonymous functions. 
> Anonymous functions, implemented in PHP 5.3, yield objects of this type. This fact used to be considered an implementation detail,
> but it can now be relied upon. Starting with PHP 5.4, this class has methods that allow further control of the anonymous function 
> after it has been created. Besides the methods listed here, this class also has an __invoke method. This is for consistency with 
> other classes that implement calling magic, as this method is not used for calling the function. 

And additionally there is a comment by "chuck at bajax dot us" put beneath it:

> This caused me some confusion a while back when I was still learning what closures were and how to use them, but what is 
> referred to as a closure in PHP isn't the same thing as what they call closures in other languages (E.G. JavaScript).
  
> In JavaScript, a closure can be thought of as a scope, when you define a function, it silently inherits the scope it's
> defined in, which is called its closure, and it retains that no matter where it's used.  It's possible for multiple functions
> to share the same closure, and they can have access to multiple closures as long as they are within their accessible scope.
  
> In PHP,  a closure is a callable class, to which you've bound your parameters manually.
  
> It's a slight distinction but one I feel bears mentioning.

--------------------------------------------------------------------------------

**ReflectionMethod**

As defined by the [PHP Documentation regarding the class](https://www.php.net/manual/de/class.reflectionclass.php):

> The ReflectionClass class reports information about a class. 

That is pretty much all they write. On top of that, from the given methods in the doc, it is apparent, that the class can give info on
whether a method is private or public etc. 

A smaller note which doesn't necessarily impact on the ReflectionMethod, but:
> With the "use" keyword a "relative" or "new" namespace can be given to the method (this is being employed at least once in the loading process) 

--------------------------------------------------------------------------------

**Extensions**

According to the [Symfony Documentation on bundles and their extensions](https://symfony.com/doc/current/bundles/extension.html):

> The extensions are classes which belong to the bundle and are supposed to load the configuration that the bundle requires and 
> has included in its directories and is therefore directly responsible for configuring its service / bundle.
> "Services created by bundles are not defined in the main config/services.yaml file used by the application but in the bundles themselves."
