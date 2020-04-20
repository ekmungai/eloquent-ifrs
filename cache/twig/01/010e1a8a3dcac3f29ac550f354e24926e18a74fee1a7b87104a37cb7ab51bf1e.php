<?php

/* macros.twig */
class __TwigTemplate_0409bd45e4038e7bd8cb7a81de92bd7bfeea9ad6fd4e58dabff4b8e806d12a0f extends Twig_Template
{
    private $source;

    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = array(
        );
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        // line 4
        echo "
";
        // line 14
        echo "
";
        // line 20
        echo "
";
        // line 26
        echo "
";
        // line 42
        echo "
";
        // line 48
        echo "
";
        // line 56
        echo "
";
        // line 60
        echo "
";
        // line 72
        echo "
";
        // line 94
        echo "
";
        // line 106
        echo "
";
        // line 110
        echo "
";
        // line 126
        echo "
";
        // line 130
        echo "
";
    }

    // line 1
    public function macro_namespace_link($__namespace__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals(array(
            "namespace" => $__namespace__,
            "varargs" => $__varargs__,
        ));

        $blocks = array();

        ob_start();
        try {
            // line 2
            echo "<a href=\"";
            echo $this->extensions['Sami\Renderer\TwigExtension']->pathForNamespace($context, (isset($context["namespace"]) || array_key_exists("namespace", $context) ? $context["namespace"] : (function () { throw new Twig_Error_Runtime('Variable "namespace" does not exist.', 2, $this->source); })()));
            echo "\">";
            echo (isset($context["namespace"]) || array_key_exists("namespace", $context) ? $context["namespace"] : (function () { throw new Twig_Error_Runtime('Variable "namespace" does not exist.', 2, $this->source); })());
            echo "</a>";

            return ('' === $tmp = ob_get_contents()) ? '' : new Twig_Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 5
    public function macro_class_link($__class__ = null, $__absolute__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals(array(
            "class" => $__class__,
            "absolute" => $__absolute__,
            "varargs" => $__varargs__,
        ));

        $blocks = array();

        ob_start();
        try {
            // line 6
            if (twig_get_attribute($this->env, $this->source, (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 6, $this->source); })()), "projectclass", array())) {
                // line 7
                echo "<a href=\"";
                echo $this->extensions['Sami\Renderer\TwigExtension']->pathForClass($context, (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 7, $this->source); })()));
                echo "\">";
            } elseif (twig_get_attribute($this->env, $this->source,             // line 8
(isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 8, $this->source); })()), "phpclass", array())) {
                // line 9
                echo "<a target=\"_blank\" href=\"http://php.net/";
                echo (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 9, $this->source); })());
                echo "\">";
            }
            // line 11
            echo $this->extensions['Sami\Renderer\TwigExtension']->abbrClass((isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 11, $this->source); })()), ((array_key_exists("absolute", $context)) ? (_twig_default_filter((isset($context["absolute"]) || array_key_exists("absolute", $context) ? $context["absolute"] : (function () { throw new Twig_Error_Runtime('Variable "absolute" does not exist.', 11, $this->source); })()), false)) : (false)));
            // line 12
            if ((twig_get_attribute($this->env, $this->source, (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 12, $this->source); })()), "projectclass", array()) || twig_get_attribute($this->env, $this->source, (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 12, $this->source); })()), "phpclass", array()))) {
                echo "</a>";
            }

            return ('' === $tmp = ob_get_contents()) ? '' : new Twig_Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 15
    public function macro_method_link($__method__ = null, $__absolute__ = null, $__classonly__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals(array(
            "method" => $__method__,
            "absolute" => $__absolute__,
            "classonly" => $__classonly__,
            "varargs" => $__varargs__,
        ));

        $blocks = array();

        ob_start();
        try {
            // line 16
            echo "<a href=\"";
            echo $this->extensions['Sami\Renderer\TwigExtension']->pathForMethod($context, (isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 16, $this->source); })()));
            echo "\">";
            // line 17
            echo $this->extensions['Sami\Renderer\TwigExtension']->abbrClass(twig_get_attribute($this->env, $this->source, (isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 17, $this->source); })()), "class", array()));
            if ( !((array_key_exists("classonly", $context)) ? (_twig_default_filter((isset($context["classonly"]) || array_key_exists("classonly", $context) ? $context["classonly"] : (function () { throw new Twig_Error_Runtime('Variable "classonly" does not exist.', 17, $this->source); })()), false)) : (false))) {
                echo "::";
                echo twig_get_attribute($this->env, $this->source, (isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 17, $this->source); })()), "name", array());
            }
            // line 18
            echo "</a>";

            return ('' === $tmp = ob_get_contents()) ? '' : new Twig_Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 21
    public function macro_property_link($__property__ = null, $__absolute__ = null, $__classonly__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals(array(
            "property" => $__property__,
            "absolute" => $__absolute__,
            "classonly" => $__classonly__,
            "varargs" => $__varargs__,
        ));

        $blocks = array();

        ob_start();
        try {
            // line 22
            echo "<a href=\"";
            echo $this->extensions['Sami\Renderer\TwigExtension']->pathForProperty($context, (isset($context["property"]) || array_key_exists("property", $context) ? $context["property"] : (function () { throw new Twig_Error_Runtime('Variable "property" does not exist.', 22, $this->source); })()));
            echo "\">";
            // line 23
            echo $this->extensions['Sami\Renderer\TwigExtension']->abbrClass(twig_get_attribute($this->env, $this->source, (isset($context["property"]) || array_key_exists("property", $context) ? $context["property"] : (function () { throw new Twig_Error_Runtime('Variable "property" does not exist.', 23, $this->source); })()), "class", array()));
            if ( !((array_key_exists("classonly", $context)) ? (_twig_default_filter((isset($context["classonly"]) || array_key_exists("classonly", $context) ? $context["classonly"] : (function () { throw new Twig_Error_Runtime('Variable "classonly" does not exist.', 23, $this->source); })()), false)) : (false))) {
                echo "#";
                echo twig_get_attribute($this->env, $this->source, (isset($context["property"]) || array_key_exists("property", $context) ? $context["property"] : (function () { throw new Twig_Error_Runtime('Variable "property" does not exist.', 23, $this->source); })()), "name", array());
            }
            // line 24
            echo "</a>";

            return ('' === $tmp = ob_get_contents()) ? '' : new Twig_Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 27
    public function macro_hint_link($__hints__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals(array(
            "hints" => $__hints__,
            "varargs" => $__varargs__,
        ));

        $blocks = array();

        ob_start();
        try {
            // line 28
            $context["__internal_cc4442d3a5bd03a0bfff92d3591eaf554167925340beccd8b2d6687a587cc8d9"] = $this;
            // line 30
            if ((isset($context["hints"]) || array_key_exists("hints", $context) ? $context["hints"] : (function () { throw new Twig_Error_Runtime('Variable "hints" does not exist.', 30, $this->source); })())) {
                // line 31
                $context['_parent'] = $context;
                $context['_seq'] = twig_ensure_traversable((isset($context["hints"]) || array_key_exists("hints", $context) ? $context["hints"] : (function () { throw new Twig_Error_Runtime('Variable "hints" does not exist.', 31, $this->source); })()));
                $context['loop'] = array(
                  'parent' => $context['_parent'],
                  'index0' => 0,
                  'index'  => 1,
                  'first'  => true,
                );
                if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof Countable)) {
                    $length = count($context['_seq']);
                    $context['loop']['revindex0'] = $length - 1;
                    $context['loop']['revindex'] = $length;
                    $context['loop']['length'] = $length;
                    $context['loop']['last'] = 1 === $length;
                }
                foreach ($context['_seq'] as $context["_key"] => $context["hint"]) {
                    // line 32
                    if (twig_get_attribute($this->env, $this->source, $context["hint"], "class", array())) {
                        // line 33
                        echo $context["__internal_cc4442d3a5bd03a0bfff92d3591eaf554167925340beccd8b2d6687a587cc8d9"]->macro_class_link(twig_get_attribute($this->env, $this->source, $context["hint"], "name", array()));
                    } elseif (twig_get_attribute($this->env, $this->source,                     // line 34
$context["hint"], "name", array())) {
                        // line 35
                        echo $this->extensions['Sami\Renderer\TwigExtension']->abbrClass(twig_get_attribute($this->env, $this->source, $context["hint"], "name", array()));
                    }
                    // line 37
                    if (twig_get_attribute($this->env, $this->source, $context["hint"], "array", array())) {
                        echo "[]";
                    }
                    // line 38
                    if ( !twig_get_attribute($this->env, $this->source, $context["loop"], "last", array())) {
                        echo "|";
                    }
                    ++$context['loop']['index0'];
                    ++$context['loop']['index'];
                    $context['loop']['first'] = false;
                    if (isset($context['loop']['length'])) {
                        --$context['loop']['revindex0'];
                        --$context['loop']['revindex'];
                        $context['loop']['last'] = 0 === $context['loop']['revindex0'];
                    }
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_iterated'], $context['_key'], $context['hint'], $context['_parent'], $context['loop']);
                $context = array_intersect_key($context, $_parent) + $_parent;
            }

            return ('' === $tmp = ob_get_contents()) ? '' : new Twig_Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 43
    public function macro_source_link($__project__ = null, $__class__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals(array(
            "project" => $__project__,
            "class" => $__class__,
            "varargs" => $__varargs__,
        ));

        $blocks = array();

        ob_start();
        try {
            // line 44
            if (twig_get_attribute($this->env, $this->source, (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 44, $this->source); })()), "sourcepath", array())) {
                // line 45
                echo "        (<a href=\"";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 45, $this->source); })()), "sourcepath", array()), "html", null, true);
                echo "\">View source</a>)";
            }

            return ('' === $tmp = ob_get_contents()) ? '' : new Twig_Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 49
    public function macro_method_source_link($__method__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals(array(
            "method" => $__method__,
            "varargs" => $__varargs__,
        ));

        $blocks = array();

        ob_start();
        try {
            // line 50
            if (twig_get_attribute($this->env, $this->source, (isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 50, $this->source); })()), "sourcepath", array())) {
                // line 51
                echo "        <a href=\"";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, (isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 51, $this->source); })()), "sourcepath", array()), "html", null, true);
                echo "\">line ";
                echo twig_get_attribute($this->env, $this->source, (isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 51, $this->source); })()), "line", array());
                echo "</a>";
            } else {
                // line 53
                echo "        line ";
                echo twig_get_attribute($this->env, $this->source, (isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 53, $this->source); })()), "line", array());
            }

            return ('' === $tmp = ob_get_contents()) ? '' : new Twig_Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 57
    public function macro_abbr_class($__class__ = null, $__absolute__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals(array(
            "class" => $__class__,
            "absolute" => $__absolute__,
            "varargs" => $__varargs__,
        ));

        $blocks = array();

        ob_start();
        try {
            // line 58
            echo "<abbr title=\"";
            echo twig_escape_filter($this->env, (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 58, $this->source); })()), "html", null, true);
            echo "\">";
            echo twig_escape_filter($this->env, ((((array_key_exists("absolute", $context)) ? (_twig_default_filter((isset($context["absolute"]) || array_key_exists("absolute", $context) ? $context["absolute"] : (function () { throw new Twig_Error_Runtime('Variable "absolute" does not exist.', 58, $this->source); })()), false)) : (false))) ? ((isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 58, $this->source); })())) : (twig_get_attribute($this->env, $this->source, (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 58, $this->source); })()), "shortname", array()))), "html", null, true);
            echo "</abbr>";

            return ('' === $tmp = ob_get_contents()) ? '' : new Twig_Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 61
    public function macro_method_parameters_signature($__method__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals(array(
            "method" => $__method__,
            "varargs" => $__varargs__,
        ));

        $blocks = array();

        ob_start();
        try {
            // line 62
            $context["__internal_3b1e27705c77838a948af0a276f0ef6f22fa34bf52b936a1eaa8991f132c57ba"] = $this->loadTemplate("macros.twig", "macros.twig", 62);
            // line 63
            echo "(";
            // line 64
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(twig_get_attribute($this->env, $this->source, (isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 64, $this->source); })()), "parameters", array()));
            $context['loop'] = array(
              'parent' => $context['_parent'],
              'index0' => 0,
              'index'  => 1,
              'first'  => true,
            );
            if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof Countable)) {
                $length = count($context['_seq']);
                $context['loop']['revindex0'] = $length - 1;
                $context['loop']['revindex'] = $length;
                $context['loop']['length'] = $length;
                $context['loop']['last'] = 1 === $length;
            }
            foreach ($context['_seq'] as $context["_key"] => $context["parameter"]) {
                // line 65
                if (twig_get_attribute($this->env, $this->source, $context["parameter"], "hashint", array())) {
                    echo $context["__internal_3b1e27705c77838a948af0a276f0ef6f22fa34bf52b936a1eaa8991f132c57ba"]->macro_hint_link(twig_get_attribute($this->env, $this->source, $context["parameter"], "hint", array()));
                    echo " ";
                }
                // line 66
                if (twig_get_attribute($this->env, $this->source, $context["parameter"], "variadic", array())) {
                    echo "...";
                }
                echo "\$";
                echo twig_get_attribute($this->env, $this->source, $context["parameter"], "name", array());
                // line 67
                if ( !(null === twig_get_attribute($this->env, $this->source, $context["parameter"], "default", array()))) {
                    echo " = ";
                    echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["parameter"], "default", array()), "html", null, true);
                }
                // line 68
                if ( !twig_get_attribute($this->env, $this->source, $context["loop"], "last", array())) {
                    echo ", ";
                }
                ++$context['loop']['index0'];
                ++$context['loop']['index'];
                $context['loop']['first'] = false;
                if (isset($context['loop']['length'])) {
                    --$context['loop']['revindex0'];
                    --$context['loop']['revindex'];
                    $context['loop']['last'] = 0 === $context['loop']['revindex0'];
                }
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['parameter'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 70
            echo ")";

            return ('' === $tmp = ob_get_contents()) ? '' : new Twig_Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 73
    public function macro_render_classes($__classes__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals(array(
            "classes" => $__classes__,
            "varargs" => $__varargs__,
        ));

        $blocks = array();

        ob_start();
        try {
            // line 74
            $context["__internal_d376587ada3f88149c69810bd99dbb87de3f0d509e5406db550b2f8309c144c8"] = $this;
            // line 75
            echo "
    <div class=\"container-fluid underlined\">
        ";
            // line 77
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable((isset($context["classes"]) || array_key_exists("classes", $context) ? $context["classes"] : (function () { throw new Twig_Error_Runtime('Variable "classes" does not exist.', 77, $this->source); })()));
            foreach ($context['_seq'] as $context["_key"] => $context["class"]) {
                // line 78
                echo "            <div class=\"row\">
                <div class=\"col-md-6\">
                    ";
                // line 80
                if (twig_get_attribute($this->env, $this->source, $context["class"], "isInterface", array())) {
                    // line 81
                    echo "                        <em>";
                    echo $context["__internal_d376587ada3f88149c69810bd99dbb87de3f0d509e5406db550b2f8309c144c8"]->macro_class_link($context["class"], true);
                    echo "</em>
                    ";
                } else {
                    // line 83
                    echo "                        ";
                    echo $context["__internal_d376587ada3f88149c69810bd99dbb87de3f0d509e5406db550b2f8309c144c8"]->macro_class_link($context["class"], true);
                    echo "
                    ";
                }
                // line 85
                echo "                    ";
                echo $context["__internal_d376587ada3f88149c69810bd99dbb87de3f0d509e5406db550b2f8309c144c8"]->macro_deprecated($context["class"]);
                echo "
                </div>
                <div class=\"col-md-6\">
                    ";
                // line 88
                echo $this->extensions['Sami\Renderer\TwigExtension']->parseDesc($context, twig_get_attribute($this->env, $this->source, $context["class"], "shortdesc", array()), $context["class"]);
                echo "
                </div>
            </div>
        ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['class'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 92
            echo "    </div>";

            return ('' === $tmp = ob_get_contents()) ? '' : new Twig_Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 95
    public function macro_breadcrumbs($__namespace__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals(array(
            "namespace" => $__namespace__,
            "varargs" => $__varargs__,
        ));

        $blocks = array();

        ob_start();
        try {
            // line 96
            echo "    ";
            $context["current_ns"] = "";
            // line 97
            echo "    ";
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(twig_split_filter($this->env, (isset($context["namespace"]) || array_key_exists("namespace", $context) ? $context["namespace"] : (function () { throw new Twig_Error_Runtime('Variable "namespace" does not exist.', 97, $this->source); })()), "\\"));
            foreach ($context['_seq'] as $context["_key"] => $context["ns"]) {
                // line 98
                if ((isset($context["current_ns"]) || array_key_exists("current_ns", $context) ? $context["current_ns"] : (function () { throw new Twig_Error_Runtime('Variable "current_ns" does not exist.', 98, $this->source); })())) {
                    // line 99
                    $context["current_ns"] = (((isset($context["current_ns"]) || array_key_exists("current_ns", $context) ? $context["current_ns"] : (function () { throw new Twig_Error_Runtime('Variable "current_ns" does not exist.', 99, $this->source); })()) . "\\") . $context["ns"]);
                } else {
                    // line 101
                    $context["current_ns"] = $context["ns"];
                }
                // line 103
                echo "<li><a href=\"";
                echo $this->extensions['Sami\Renderer\TwigExtension']->pathForNamespace($context, (isset($context["current_ns"]) || array_key_exists("current_ns", $context) ? $context["current_ns"] : (function () { throw new Twig_Error_Runtime('Variable "current_ns" does not exist.', 103, $this->source); })()));
                echo "\">";
                echo $context["ns"];
                echo "</a></li><li class=\"backslash\">\\</li>";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['ns'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;

            return ('' === $tmp = ob_get_contents()) ? '' : new Twig_Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 107
    public function macro_deprecated($__reflection__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals(array(
            "reflection" => $__reflection__,
            "varargs" => $__varargs__,
        ));

        $blocks = array();

        ob_start();
        try {
            // line 108
            echo "    ";
            if (twig_get_attribute($this->env, $this->source, (isset($context["reflection"]) || array_key_exists("reflection", $context) ? $context["reflection"] : (function () { throw new Twig_Error_Runtime('Variable "reflection" does not exist.', 108, $this->source); })()), "deprecated", array())) {
                echo "<small><sup><span class=\"label label-danger\">deprecated</span></sup></small>";
            }

            return ('' === $tmp = ob_get_contents()) ? '' : new Twig_Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 111
    public function macro_deprecations($__reflection__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals(array(
            "reflection" => $__reflection__,
            "varargs" => $__varargs__,
        ));

        $blocks = array();

        ob_start();
        try {
            // line 112
            echo "    ";
            $context["__internal_47102e29898f3d0dd80c6706db1fc5549b651c4e58ebd8be566ac56fb7dcd387"] = $this;
            // line 113
            echo "
    ";
            // line 114
            if (twig_get_attribute($this->env, $this->source, (isset($context["reflection"]) || array_key_exists("reflection", $context) ? $context["reflection"] : (function () { throw new Twig_Error_Runtime('Variable "reflection" does not exist.', 114, $this->source); })()), "deprecated", array())) {
                // line 115
                echo "        <p>
            ";
                // line 116
                echo $context["__internal_47102e29898f3d0dd80c6706db1fc5549b651c4e58ebd8be566ac56fb7dcd387"]->macro_deprecated((isset($context["reflection"]) || array_key_exists("reflection", $context) ? $context["reflection"] : (function () { throw new Twig_Error_Runtime('Variable "reflection" does not exist.', 116, $this->source); })()));
                echo "
            ";
                // line 117
                $context['_parent'] = $context;
                $context['_seq'] = twig_ensure_traversable(twig_get_attribute($this->env, $this->source, (isset($context["reflection"]) || array_key_exists("reflection", $context) ? $context["reflection"] : (function () { throw new Twig_Error_Runtime('Variable "reflection" does not exist.', 117, $this->source); })()), "deprecated", array()));
                foreach ($context['_seq'] as $context["_key"] => $context["tag"]) {
                    // line 118
                    echo "                <tr>
                    <td>";
                    // line 119
                    echo twig_get_attribute($this->env, $this->source, $context["tag"], 0, array(), "array");
                    echo "</td>
                    <td>";
                    // line 120
                    echo twig_join_filter(twig_slice($this->env, $context["tag"], 1, null), " ");
                    echo "</td>
                </tr>
            ";
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_iterated'], $context['_key'], $context['tag'], $context['_parent'], $context['loop']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 123
                echo "        </p>
    ";
            }

            return ('' === $tmp = ob_get_contents()) ? '' : new Twig_Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 127
    public function macro_todo($__reflection__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals(array(
            "reflection" => $__reflection__,
            "varargs" => $__varargs__,
        ));

        $blocks = array();

        ob_start();
        try {
            // line 128
            echo "        ";
            if (twig_get_attribute($this->env, $this->source, (isset($context["reflection"]) || array_key_exists("reflection", $context) ? $context["reflection"] : (function () { throw new Twig_Error_Runtime('Variable "reflection" does not exist.', 128, $this->source); })()), "todo", array())) {
                echo "<small><sup><span class=\"label label-info\">todo</span></sup></small>";
            }

            return ('' === $tmp = ob_get_contents()) ? '' : new Twig_Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    // line 131
    public function macro_todos($__reflection__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals(array(
            "reflection" => $__reflection__,
            "varargs" => $__varargs__,
        ));

        $blocks = array();

        ob_start();
        try {
            // line 132
            echo "        ";
            $context["__internal_9d1707877c0fec55fc99356f57c4edfc181c7dc5f7ed94e44128a735f301756c"] = $this;
            // line 133
            echo "
        ";
            // line 134
            if (twig_get_attribute($this->env, $this->source, (isset($context["reflection"]) || array_key_exists("reflection", $context) ? $context["reflection"] : (function () { throw new Twig_Error_Runtime('Variable "reflection" does not exist.', 134, $this->source); })()), "todo", array())) {
                // line 135
                echo "            <p>
                ";
                // line 136
                echo $context["__internal_9d1707877c0fec55fc99356f57c4edfc181c7dc5f7ed94e44128a735f301756c"]->macro_todo((isset($context["reflection"]) || array_key_exists("reflection", $context) ? $context["reflection"] : (function () { throw new Twig_Error_Runtime('Variable "reflection" does not exist.', 136, $this->source); })()));
                echo "
                ";
                // line 137
                $context['_parent'] = $context;
                $context['_seq'] = twig_ensure_traversable(twig_get_attribute($this->env, $this->source, (isset($context["reflection"]) || array_key_exists("reflection", $context) ? $context["reflection"] : (function () { throw new Twig_Error_Runtime('Variable "reflection" does not exist.', 137, $this->source); })()), "todo", array()));
                foreach ($context['_seq'] as $context["_key"] => $context["tag"]) {
                    // line 138
                    echo "                    <tr>
                        <td>";
                    // line 139
                    echo twig_get_attribute($this->env, $this->source, $context["tag"], 0, array(), "array");
                    echo "</td>
                        <td>";
                    // line 140
                    echo twig_join_filter(twig_slice($this->env, $context["tag"], 1, null), " ");
                    echo "</td>
                        </tr>
                ";
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_iterated'], $context['_key'], $context['tag'], $context['_parent'], $context['loop']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 143
                echo "            </p>
        ";
            }

            return ('' === $tmp = ob_get_contents()) ? '' : new Twig_Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    public function getTemplateName()
    {
        return "macros.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  690 => 143,  681 => 140,  677 => 139,  674 => 138,  670 => 137,  666 => 136,  663 => 135,  661 => 134,  658 => 133,  655 => 132,  643 => 131,  631 => 128,  619 => 127,  608 => 123,  599 => 120,  595 => 119,  592 => 118,  588 => 117,  584 => 116,  581 => 115,  579 => 114,  576 => 113,  573 => 112,  561 => 111,  549 => 108,  537 => 107,  520 => 103,  517 => 101,  514 => 99,  512 => 98,  507 => 97,  504 => 96,  492 => 95,  483 => 92,  473 => 88,  466 => 85,  460 => 83,  454 => 81,  452 => 80,  448 => 78,  444 => 77,  440 => 75,  438 => 74,  426 => 73,  417 => 70,  401 => 68,  396 => 67,  390 => 66,  385 => 65,  368 => 64,  366 => 63,  364 => 62,  352 => 61,  339 => 58,  326 => 57,  315 => 53,  308 => 51,  306 => 50,  294 => 49,  282 => 45,  280 => 44,  267 => 43,  243 => 38,  239 => 37,  236 => 35,  234 => 34,  232 => 33,  230 => 32,  213 => 31,  211 => 30,  209 => 28,  197 => 27,  188 => 24,  182 => 23,  178 => 22,  164 => 21,  155 => 18,  149 => 17,  145 => 16,  131 => 15,  120 => 12,  118 => 11,  113 => 9,  111 => 8,  107 => 7,  105 => 6,  92 => 5,  79 => 2,  67 => 1,  62 => 130,  59 => 126,  56 => 110,  53 => 106,  50 => 94,  47 => 72,  44 => 60,  41 => 56,  38 => 48,  35 => 42,  32 => 26,  29 => 20,  26 => 14,  23 => 4,);
    }

    public function getSourceContext()
    {
        return new Twig_Source("{% macro namespace_link(namespace) -%}
    <a href=\"{{ namespace_path(namespace) }}\">{{ namespace|raw }}</a>
{%- endmacro %}

{% macro class_link(class, absolute) -%}
    {%- if class.projectclass -%}
        <a href=\"{{ class_path(class) }}\">
    {%- elseif class.phpclass -%}
        <a target=\"_blank\" href=\"http://php.net/{{ class|raw }}\">
    {%- endif %}
    {{- abbr_class(class, absolute|default(false)) }}
    {%- if class.projectclass or class.phpclass %}</a>{% endif %}
{%- endmacro %}

{% macro method_link(method, absolute, classonly) -%}
    <a href=\"{{ method_path(method) }}\">
        {{- abbr_class(method.class) }}{% if not classonly|default(false) %}::{{ method.name|raw }}{% endif -%}
    </a>
{%- endmacro %}

{% macro property_link(property, absolute, classonly) -%}
    <a href=\"{{ property_path(property) }}\">
        {{- abbr_class(property.class) }}{% if not classonly|default(false) %}#{{ property.name|raw }}{% endif -%}
    </a>
{%- endmacro %}

{% macro hint_link(hints) -%}
    {%- from _self import class_link %}

    {%- if hints %}
        {%- for hint in hints %}
            {%- if hint.class %}
                {{- class_link(hint.name) }}
            {%- elseif hint.name %}
                {{- abbr_class(hint.name) }}
            {%- endif %}
            {%- if hint.array %}[]{% endif %}
            {%- if not loop.last %}|{% endif %}
        {%- endfor %}
    {%- endif %}
{%- endmacro %}

{% macro source_link(project, class) -%}
    {% if class.sourcepath %}
        (<a href=\"{{ class.sourcepath }}\">View source</a>)
    {%- endif %}
{%- endmacro %}

{% macro method_source_link(method) -%}
    {% if method.sourcepath %}
        <a href=\"{{ method.sourcepath }}\">line {{ method.line|raw }}</a>
    {%- else %}
        line {{ method.line|raw }}
    {%- endif %}
{%- endmacro %}

{% macro abbr_class(class, absolute) -%}
    <abbr title=\"{{ class }}\">{{ absolute|default(false) ? class : class.shortname }}</abbr>
{%- endmacro %}

{% macro method_parameters_signature(method) -%}
    {%- from \"macros.twig\" import hint_link -%}
    (
        {%- for parameter in method.parameters %}
            {%- if parameter.hashint %}{{ hint_link(parameter.hint) }} {% endif -%}
            {%- if parameter.variadic %}...{% endif %}\${{ parameter.name|raw }}
            {%- if parameter.default is not null %} = {{ parameter.default }}{% endif %}
            {%- if not loop.last %}, {% endif %}
        {%- endfor -%}
    )
{%- endmacro %}

{% macro render_classes(classes) -%}
    {% from _self import class_link, deprecated %}

    <div class=\"container-fluid underlined\">
        {% for class in classes %}
            <div class=\"row\">
                <div class=\"col-md-6\">
                    {% if class.isInterface %}
                        <em>{{ class_link(class, true) }}</em>
                    {% else %}
                        {{ class_link(class, true) }}
                    {% endif %}
                    {{ deprecated(class) }}
                </div>
                <div class=\"col-md-6\">
                    {{ class.shortdesc|desc(class) }}
                </div>
            </div>
        {% endfor %}
    </div>
{%- endmacro %}

{% macro breadcrumbs(namespace) %}
    {% set current_ns = '' %}
    {% for ns in namespace|split('\\\\') %}
        {%- if current_ns -%}
            {% set current_ns = current_ns ~ '\\\\' ~ ns %}
        {%- else -%}
            {% set current_ns = ns %}
        {%- endif -%}
        <li><a href=\"{{ namespace_path(current_ns) }}\">{{ ns|raw }}</a></li><li class=\"backslash\">\\</li>
    {%- endfor %}
{% endmacro %}

{% macro deprecated(reflection) %}
    {% if reflection.deprecated %}<small><sup><span class=\"label label-danger\">deprecated</span></sup></small>{% endif %}
{% endmacro %}

{% macro deprecations(reflection) %}
    {% from _self import deprecated %}

    {% if reflection.deprecated %}
        <p>
            {{ deprecated(reflection )}}
            {% for tag in reflection.deprecated %}
                <tr>
                    <td>{{ tag[0]|raw }}</td>
                    <td>{{ tag[1:]|join(' ')|raw }}</td>
                </tr>
            {% endfor %}
        </p>
    {% endif %}
{% endmacro %}

{% macro todo(reflection) %}
        {% if reflection.todo %}<small><sup><span class=\"label label-info\">todo</span></sup></small>{% endif %}
{% endmacro %}

{% macro todos(reflection) %}
        {% from _self import todo %}

        {% if reflection.todo %}
            <p>
                {{ todo(reflection )}}
                {% for tag in reflection.todo %}
                    <tr>
                        <td>{{ tag[0]|raw }}</td>
                        <td>{{ tag[1:]|join(' ')|raw }}</td>
                        </tr>
                {% endfor %}
            </p>
        {% endif %}
{% endmacro %}
", "macros.twig", "phar://C:/sami/sami.phar/Sami/Resources/themes\\default/macros.twig");
    }
}
