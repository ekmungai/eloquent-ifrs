<?php

/* class.twig */
class __TwigTemplate_3acdd1a3e558627f4e06ba3565ce38e982f79b49894d69b247d86ae4f30b9a98 extends Twig_Template
{
    private $source;

    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        // line 1
        $this->parent = $this->loadTemplate("layout/layout.twig", "class.twig", 1);
        $this->blocks = array(
            'title' => array($this, 'block_title'),
            'body_class' => array($this, 'block_body_class'),
            'page_id' => array($this, 'block_page_id'),
            'below_menu' => array($this, 'block_below_menu'),
            'page_content' => array($this, 'block_page_content'),
            'class_signature' => array($this, 'block_class_signature'),
            'method_signature' => array($this, 'block_method_signature'),
            'method_parameters_signature' => array($this, 'block_method_parameters_signature'),
            'parameters' => array($this, 'block_parameters'),
            'return' => array($this, 'block_return'),
            'exceptions' => array($this, 'block_exceptions'),
            'see' => array($this, 'block_see'),
            'constants' => array($this, 'block_constants'),
            'properties' => array($this, 'block_properties'),
            'methods' => array($this, 'block_methods'),
            'methods_details' => array($this, 'block_methods_details'),
            'method' => array($this, 'block_method'),
        );
    }

    protected function doGetParent(array $context)
    {
        return "layout/layout.twig";
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        // line 2
        $context["__internal_c100660e1ce6a187b28d0d963856da8925c2f4f210b6c83e5fb12064ced43621"] = $this->loadTemplate("macros.twig", "class.twig", 2);
        // line 1
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_title($context, array $blocks = array())
    {
        echo (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 3, $this->source); })());
        echo " | ";
        $this->displayParentBlock("title", $context, $blocks);
    }

    // line 4
    public function block_body_class($context, array $blocks = array())
    {
        echo "class";
    }

    // line 5
    public function block_page_id($context, array $blocks = array())
    {
        echo twig_escape_filter($this->env, ("class:" . twig_replace_filter(twig_get_attribute($this->env, $this->source, (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 5, $this->source); })()), "name", array()), array("\\" => "_"))), "html", null, true);
    }

    // line 7
    public function block_below_menu($context, array $blocks = array())
    {
        // line 8
        echo "    ";
        if (twig_get_attribute($this->env, $this->source, (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 8, $this->source); })()), "namespace", array())) {
            // line 9
            echo "        <div class=\"namespace-breadcrumbs\">
            <ol class=\"breadcrumb\">
                <li><span class=\"label label-default\">";
            // line 11
            echo twig_get_attribute($this->env, $this->source, (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 11, $this->source); })()), "categoryName", array());
            echo "</span></li>
                ";
            // line 12
            echo $context["__internal_c100660e1ce6a187b28d0d963856da8925c2f4f210b6c83e5fb12064ced43621"]->macro_breadcrumbs(twig_get_attribute($this->env, $this->source, (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 12, $this->source); })()), "namespace", array()));
            // line 13
            echo "<li>";
            echo twig_get_attribute($this->env, $this->source, (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 13, $this->source); })()), "shortname", array());
            echo "</li>
            </ol>
        </div>
    ";
        }
    }

    // line 19
    public function block_page_content($context, array $blocks = array())
    {
        // line 20
        echo "
    <div class=\"page-header\">
        <h1>
            ";
        // line 23
        echo twig_last($this->env, twig_split_filter($this->env, twig_get_attribute($this->env, $this->source, (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 23, $this->source); })()), "name", array()), "\\"));
        echo "
            ";
        // line 24
        echo $context["__internal_c100660e1ce6a187b28d0d963856da8925c2f4f210b6c83e5fb12064ced43621"]->macro_deprecated((isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 24, $this->source); })()));
        echo "
        </h1>
    </div>

    <p>";
        // line 28
        $this->displayBlock("class_signature", $context, $blocks);
        echo "</p>

    ";
        // line 30
        echo $context["__internal_c100660e1ce6a187b28d0d963856da8925c2f4f210b6c83e5fb12064ced43621"]->macro_deprecations((isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 30, $this->source); })()));
        echo "

    ";
        // line 32
        if ((twig_get_attribute($this->env, $this->source, (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 32, $this->source); })()), "shortdesc", array()) || twig_get_attribute($this->env, $this->source, (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 32, $this->source); })()), "longdesc", array()))) {
            // line 33
            echo "        <div class=\"description\">
            ";
            // line 34
            if (twig_get_attribute($this->env, $this->source, (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 34, $this->source); })()), "shortdesc", array())) {
                // line 35
                echo "<p>";
                echo $this->extensions['Sami\Renderer\TwigExtension']->parseDesc($context, twig_get_attribute($this->env, $this->source, (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 35, $this->source); })()), "shortdesc", array()), (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 35, $this->source); })()));
                echo "</p>";
            }
            // line 37
            echo "            ";
            if (twig_get_attribute($this->env, $this->source, (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 37, $this->source); })()), "longdesc", array())) {
                // line 38
                echo "<p>";
                echo $this->extensions['Sami\Renderer\TwigExtension']->parseDesc($context, twig_get_attribute($this->env, $this->source, (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 38, $this->source); })()), "longdesc", array()), (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 38, $this->source); })()));
                echo "</p>";
            }
            // line 40
            echo "            ";
            if ((twig_get_attribute($this->env, $this->source, (isset($context["project"]) || array_key_exists("project", $context) ? $context["project"] : (function () { throw new Twig_Error_Runtime('Variable "project" does not exist.', 40, $this->source); })()), "config", array(0 => "insert_todos"), "method") == true)) {
                // line 41
                echo "                ";
                echo $context["__internal_c100660e1ce6a187b28d0d963856da8925c2f4f210b6c83e5fb12064ced43621"]->macro_todos((isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 41, $this->source); })()));
                echo "
            ";
            }
            // line 43
            echo "        </div>
    ";
        }
        // line 45
        echo "
    ";
        // line 46
        if ((isset($context["traits"]) || array_key_exists("traits", $context) ? $context["traits"] : (function () { throw new Twig_Error_Runtime('Variable "traits" does not exist.', 46, $this->source); })())) {
            // line 47
            echo "        <h2>Traits</h2>

        ";
            // line 49
            echo $context["__internal_c100660e1ce6a187b28d0d963856da8925c2f4f210b6c83e5fb12064ced43621"]->macro_render_classes((isset($context["traits"]) || array_key_exists("traits", $context) ? $context["traits"] : (function () { throw new Twig_Error_Runtime('Variable "traits" does not exist.', 49, $this->source); })()));
            echo "
    ";
        }
        // line 51
        echo "
    ";
        // line 52
        if ((isset($context["constants"]) || array_key_exists("constants", $context) ? $context["constants"] : (function () { throw new Twig_Error_Runtime('Variable "constants" does not exist.', 52, $this->source); })())) {
            // line 53
            echo "        <h2>Constants</h2>

        ";
            // line 55
            $this->displayBlock("constants", $context, $blocks);
            echo "
    ";
        }
        // line 57
        echo "
    ";
        // line 58
        if ((isset($context["properties"]) || array_key_exists("properties", $context) ? $context["properties"] : (function () { throw new Twig_Error_Runtime('Variable "properties" does not exist.', 58, $this->source); })())) {
            // line 59
            echo "        <h2>Properties</h2>

        ";
            // line 61
            $this->displayBlock("properties", $context, $blocks);
            echo "
    ";
        }
        // line 63
        echo "
    ";
        // line 64
        if ((isset($context["methods"]) || array_key_exists("methods", $context) ? $context["methods"] : (function () { throw new Twig_Error_Runtime('Variable "methods" does not exist.', 64, $this->source); })())) {
            // line 65
            echo "        <h2>Methods</h2>

        ";
            // line 67
            $this->displayBlock("methods", $context, $blocks);
            echo "

        <h2>Details</h2>

        ";
            // line 71
            $this->displayBlock("methods_details", $context, $blocks);
            echo "
    ";
        }
        // line 73
        echo "
";
    }

    // line 76
    public function block_class_signature($context, array $blocks = array())
    {
        // line 77
        if (( !twig_get_attribute($this->env, $this->source, (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 77, $this->source); })()), "interface", array()) && twig_get_attribute($this->env, $this->source, (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 77, $this->source); })()), "abstract", array()))) {
            echo "abstract ";
        }
        // line 78
        echo "    ";
        echo twig_get_attribute($this->env, $this->source, (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 78, $this->source); })()), "categoryName", array());
        echo "
    <strong>";
        // line 79
        echo twig_get_attribute($this->env, $this->source, (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 79, $this->source); })()), "shortname", array());
        echo "</strong>";
        // line 80
        if (twig_get_attribute($this->env, $this->source, (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 80, $this->source); })()), "parent", array())) {
            // line 81
            echo "        extends ";
            echo $context["__internal_c100660e1ce6a187b28d0d963856da8925c2f4f210b6c83e5fb12064ced43621"]->macro_class_link(twig_get_attribute($this->env, $this->source, (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 81, $this->source); })()), "parent", array()));
        }
        // line 83
        if ((twig_length_filter($this->env, twig_get_attribute($this->env, $this->source, (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 83, $this->source); })()), "interfaces", array())) > 0)) {
            // line 84
            echo "        implements
        ";
            // line 85
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(twig_get_attribute($this->env, $this->source, (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 85, $this->source); })()), "interfaces", array()));
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
            foreach ($context['_seq'] as $context["_key"] => $context["interface"]) {
                // line 86
                echo $context["__internal_c100660e1ce6a187b28d0d963856da8925c2f4f210b6c83e5fb12064ced43621"]->macro_class_link($context["interface"]);
                // line 87
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
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['interface'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
        }
        // line 90
        echo $context["__internal_c100660e1ce6a187b28d0d963856da8925c2f4f210b6c83e5fb12064ced43621"]->macro_source_link((isset($context["project"]) || array_key_exists("project", $context) ? $context["project"] : (function () { throw new Twig_Error_Runtime('Variable "project" does not exist.', 90, $this->source); })()), (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 90, $this->source); })()));
        echo "
";
    }

    // line 93
    public function block_method_signature($context, array $blocks = array())
    {
        // line 94
        if (twig_get_attribute($this->env, $this->source, (isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 94, $this->source); })()), "final", array())) {
            echo "final";
        }
        // line 95
        echo "    ";
        if (twig_get_attribute($this->env, $this->source, (isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 95, $this->source); })()), "abstract", array())) {
            echo "abstract";
        }
        // line 96
        echo "    ";
        if (twig_get_attribute($this->env, $this->source, (isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 96, $this->source); })()), "static", array())) {
            echo "static";
        }
        // line 97
        echo "    ";
        if (twig_get_attribute($this->env, $this->source, (isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 97, $this->source); })()), "protected", array())) {
            echo "protected";
        }
        // line 98
        echo "    ";
        if (twig_get_attribute($this->env, $this->source, (isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 98, $this->source); })()), "private", array())) {
            echo "private";
        }
        // line 99
        echo "    ";
        echo $context["__internal_c100660e1ce6a187b28d0d963856da8925c2f4f210b6c83e5fb12064ced43621"]->macro_hint_link(twig_get_attribute($this->env, $this->source, (isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 99, $this->source); })()), "hint", array()));
        echo "
    <strong>";
        // line 100
        echo twig_get_attribute($this->env, $this->source, (isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 100, $this->source); })()), "name", array());
        echo "</strong>";
        $this->displayBlock("method_parameters_signature", $context, $blocks);
    }

    // line 103
    public function block_method_parameters_signature($context, array $blocks = array())
    {
        // line 104
        $context["__internal_dd8ef09f6272d0f8c7b2290b549331e5682882f06933d406daefc80c41f4dfd1"] = $this->loadTemplate("macros.twig", "class.twig", 104);
        // line 105
        echo $context["__internal_dd8ef09f6272d0f8c7b2290b549331e5682882f06933d406daefc80c41f4dfd1"]->macro_method_parameters_signature((isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 105, $this->source); })()));
        echo "
    ";
        // line 106
        echo $context["__internal_c100660e1ce6a187b28d0d963856da8925c2f4f210b6c83e5fb12064ced43621"]->macro_deprecated((isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 106, $this->source); })()));
    }

    // line 109
    public function block_parameters($context, array $blocks = array())
    {
        // line 110
        echo "    <table class=\"table table-condensed\">
        ";
        // line 111
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(twig_get_attribute($this->env, $this->source, (isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 111, $this->source); })()), "parameters", array()));
        foreach ($context['_seq'] as $context["_key"] => $context["parameter"]) {
            // line 112
            echo "            <tr>
                <td>";
            // line 113
            if (twig_get_attribute($this->env, $this->source, $context["parameter"], "hint", array())) {
                echo $context["__internal_c100660e1ce6a187b28d0d963856da8925c2f4f210b6c83e5fb12064ced43621"]->macro_hint_link(twig_get_attribute($this->env, $this->source, $context["parameter"], "hint", array()));
            }
            echo "</td>
                <td>";
            // line 114
            if (twig_get_attribute($this->env, $this->source, $context["parameter"], "variadic", array())) {
                echo "...";
            }
            echo "\$";
            echo twig_get_attribute($this->env, $this->source, $context["parameter"], "name", array());
            echo "</td>
                <td>";
            // line 115
            echo $this->extensions['Sami\Renderer\TwigExtension']->parseDesc($context, twig_get_attribute($this->env, $this->source, $context["parameter"], "shortdesc", array()), (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 115, $this->source); })()));
            echo "</td>
            </tr>
        ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['parameter'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 118
        echo "    </table>
";
    }

    // line 121
    public function block_return($context, array $blocks = array())
    {
        // line 122
        echo "    <table class=\"table table-condensed\">
        <tr>
            <td>";
        // line 124
        echo $context["__internal_c100660e1ce6a187b28d0d963856da8925c2f4f210b6c83e5fb12064ced43621"]->macro_hint_link(twig_get_attribute($this->env, $this->source, (isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 124, $this->source); })()), "hint", array()));
        echo "</td>
            <td>";
        // line 125
        echo $this->extensions['Sami\Renderer\TwigExtension']->parseDesc($context, twig_get_attribute($this->env, $this->source, (isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 125, $this->source); })()), "hintDesc", array()), (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 125, $this->source); })()));
        echo "</td>
        </tr>
    </table>
";
    }

    // line 130
    public function block_exceptions($context, array $blocks = array())
    {
        // line 131
        echo "    <table class=\"table table-condensed\">
        ";
        // line 132
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(twig_get_attribute($this->env, $this->source, (isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 132, $this->source); })()), "exceptions", array()));
        foreach ($context['_seq'] as $context["_key"] => $context["exception"]) {
            // line 133
            echo "            <tr>
                <td>";
            // line 134
            echo $context["__internal_c100660e1ce6a187b28d0d963856da8925c2f4f210b6c83e5fb12064ced43621"]->macro_class_link(twig_get_attribute($this->env, $this->source, $context["exception"], 0, array(), "array"));
            echo "</td>
                <td>";
            // line 135
            echo $this->extensions['Sami\Renderer\TwigExtension']->parseDesc($context, twig_get_attribute($this->env, $this->source, $context["exception"], 1, array(), "array"), (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 135, $this->source); })()));
            echo "</td>
            </tr>
        ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['exception'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 138
        echo "    </table>
";
    }

    // line 141
    public function block_see($context, array $blocks = array())
    {
        // line 142
        echo "    <table class=\"table table-condensed\">
        ";
        // line 143
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(twig_get_attribute($this->env, $this->source, (isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 143, $this->source); })()), "see", array()));
        foreach ($context['_seq'] as $context["_key"] => $context["see"]) {
            // line 144
            echo "            <tr>
                <td>
                    ";
            // line 146
            if (twig_get_attribute($this->env, $this->source, $context["see"], 4, array(), "array")) {
                // line 147
                echo "                        <a href=\"";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["see"], 4, array(), "array"), "html", null, true);
                echo "\">";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["see"], 4, array(), "array"), "html", null, true);
                echo "</a>
                    ";
            } elseif (twig_get_attribute($this->env, $this->source,             // line 148
$context["see"], 3, array(), "array")) {
                // line 149
                echo "                        ";
                echo $context["__internal_c100660e1ce6a187b28d0d963856da8925c2f4f210b6c83e5fb12064ced43621"]->macro_method_link(twig_get_attribute($this->env, $this->source, $context["see"], 3, array(), "array"), false, false);
                echo "
                    ";
            } elseif (twig_get_attribute($this->env, $this->source,             // line 150
$context["see"], 2, array(), "array")) {
                // line 151
                echo "                        ";
                echo $context["__internal_c100660e1ce6a187b28d0d963856da8925c2f4f210b6c83e5fb12064ced43621"]->macro_class_link(twig_get_attribute($this->env, $this->source, $context["see"], 2, array(), "array"));
                echo "
                    ";
            } else {
                // line 153
                echo "                        ";
                echo twig_get_attribute($this->env, $this->source, $context["see"], 0, array(), "array");
                echo "
                    ";
            }
            // line 155
            echo "                </td>
                <td>";
            // line 156
            echo twig_get_attribute($this->env, $this->source, $context["see"], 1, array(), "array");
            echo "</td>
            </tr>
        ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['see'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 159
        echo "    </table>
";
    }

    // line 162
    public function block_constants($context, array $blocks = array())
    {
        // line 163
        echo "    <table class=\"table table-condensed\">
        ";
        // line 164
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable((isset($context["constants"]) || array_key_exists("constants", $context) ? $context["constants"] : (function () { throw new Twig_Error_Runtime('Variable "constants" does not exist.', 164, $this->source); })()));
        foreach ($context['_seq'] as $context["_key"] => $context["constant"]) {
            // line 165
            echo "            <tr>
                <td>";
            // line 166
            echo twig_get_attribute($this->env, $this->source, $context["constant"], "name", array());
            echo "</td>
                <td class=\"last\">
                    <p><em>";
            // line 168
            echo $this->extensions['Sami\Renderer\TwigExtension']->parseDesc($context, twig_get_attribute($this->env, $this->source, $context["constant"], "shortdesc", array()), (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 168, $this->source); })()));
            echo "</em></p>
                    <p>";
            // line 169
            echo $this->extensions['Sami\Renderer\TwigExtension']->parseDesc($context, twig_get_attribute($this->env, $this->source, $context["constant"], "longdesc", array()), (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 169, $this->source); })()));
            echo "</p>
                </td>
            </tr>
        ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['constant'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 173
        echo "    </table>
";
    }

    // line 176
    public function block_properties($context, array $blocks = array())
    {
        // line 177
        echo "    <table class=\"table table-condensed\">
        ";
        // line 178
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable((isset($context["properties"]) || array_key_exists("properties", $context) ? $context["properties"] : (function () { throw new Twig_Error_Runtime('Variable "properties" does not exist.', 178, $this->source); })()));
        foreach ($context['_seq'] as $context["_key"] => $context["property"]) {
            // line 179
            echo "            <tr>
                <td class=\"type\" id=\"property_";
            // line 180
            echo twig_get_attribute($this->env, $this->source, $context["property"], "name", array());
            echo "\">
                    ";
            // line 181
            if (twig_get_attribute($this->env, $this->source, $context["property"], "static", array())) {
                echo "static";
            }
            // line 182
            echo "                    ";
            if (twig_get_attribute($this->env, $this->source, $context["property"], "protected", array())) {
                echo "protected";
            }
            // line 183
            echo "                    ";
            if (twig_get_attribute($this->env, $this->source, $context["property"], "private", array())) {
                echo "private";
            }
            // line 184
            echo "                    ";
            echo $context["__internal_c100660e1ce6a187b28d0d963856da8925c2f4f210b6c83e5fb12064ced43621"]->macro_hint_link(twig_get_attribute($this->env, $this->source, $context["property"], "hint", array()));
            echo "
                </td>
                <td>\$";
            // line 186
            echo twig_get_attribute($this->env, $this->source, $context["property"], "name", array());
            echo "</td>
                <td class=\"last\">";
            // line 187
            echo $this->extensions['Sami\Renderer\TwigExtension']->parseDesc($context, twig_get_attribute($this->env, $this->source, $context["property"], "shortdesc", array()), (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 187, $this->source); })()));
            echo "</td>
                <td>";
            // line 189
            if ( !(twig_get_attribute($this->env, $this->source, $context["property"], "class", array()) === (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 189, $this->source); })()))) {
                // line 190
                echo "<small>from&nbsp;";
                echo $context["__internal_c100660e1ce6a187b28d0d963856da8925c2f4f210b6c83e5fb12064ced43621"]->macro_property_link($context["property"], false, true);
                echo "</small>";
            }
            // line 192
            echo "</td>
            </tr>
        ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['property'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 195
        echo "    </table>
";
    }

    // line 198
    public function block_methods($context, array $blocks = array())
    {
        // line 199
        echo "    <div class=\"container-fluid underlined\">
        ";
        // line 200
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable((isset($context["methods"]) || array_key_exists("methods", $context) ? $context["methods"] : (function () { throw new Twig_Error_Runtime('Variable "methods" does not exist.', 200, $this->source); })()));
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
        foreach ($context['_seq'] as $context["_key"] => $context["method"]) {
            // line 201
            echo "            <div class=\"row\">
                <div class=\"col-md-2 type\">
                    ";
            // line 203
            if (twig_get_attribute($this->env, $this->source, $context["method"], "static", array())) {
                echo "static&nbsp;";
            }
            echo $context["__internal_c100660e1ce6a187b28d0d963856da8925c2f4f210b6c83e5fb12064ced43621"]->macro_hint_link(twig_get_attribute($this->env, $this->source, $context["method"], "hint", array()));
            echo "
                </div>
                <div class=\"col-md-8 type\">
                    <a href=\"#method_";
            // line 206
            echo twig_get_attribute($this->env, $this->source, $context["method"], "name", array());
            echo "\">";
            echo twig_get_attribute($this->env, $this->source, $context["method"], "name", array());
            echo "</a>";
            $this->displayBlock("method_parameters_signature", $context, $blocks);
            echo "
                    ";
            // line 207
            if ( !twig_get_attribute($this->env, $this->source, $context["method"], "shortdesc", array())) {
                // line 208
                echo "                        <p class=\"no-description\">No description</p>
                    ";
            } else {
                // line 210
                echo "                        <p>";
                echo $this->extensions['Sami\Renderer\TwigExtension']->parseDesc($context, twig_get_attribute($this->env, $this->source, $context["method"], "shortdesc", array()), (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 210, $this->source); })()));
                echo "</p>";
            }
            // line 212
            echo "                </div>
                <div class=\"col-md-2\">";
            // line 214
            if ( !(twig_get_attribute($this->env, $this->source, $context["method"], "class", array()) === (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 214, $this->source); })()))) {
                // line 215
                echo "<small>from&nbsp;";
                echo $context["__internal_c100660e1ce6a187b28d0d963856da8925c2f4f210b6c83e5fb12064ced43621"]->macro_method_link($context["method"], false, true);
                echo "</small>";
            }
            // line 217
            echo "</div>
            </div>
        ";
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
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['method'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 220
        echo "    </div>
";
    }

    // line 223
    public function block_methods_details($context, array $blocks = array())
    {
        // line 224
        echo "    <div id=\"method-details\">
        ";
        // line 225
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable((isset($context["methods"]) || array_key_exists("methods", $context) ? $context["methods"] : (function () { throw new Twig_Error_Runtime('Variable "methods" does not exist.', 225, $this->source); })()));
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
        foreach ($context['_seq'] as $context["_key"] => $context["method"]) {
            // line 226
            echo "            <div class=\"method-item\">
                ";
            // line 227
            $this->displayBlock("method", $context, $blocks);
            echo "
            </div>
        ";
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
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['method'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 230
        echo "    </div>
";
    }

    // line 233
    public function block_method($context, array $blocks = array())
    {
        // line 234
        echo "    <h3 id=\"method_";
        echo twig_get_attribute($this->env, $this->source, (isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 234, $this->source); })()), "name", array());
        echo "\">
        <div class=\"location\">";
        // line 235
        if ( !(twig_get_attribute($this->env, $this->source, (isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 235, $this->source); })()), "class", array()) === (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 235, $this->source); })()))) {
            echo "in ";
            echo $context["__internal_c100660e1ce6a187b28d0d963856da8925c2f4f210b6c83e5fb12064ced43621"]->macro_method_link((isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 235, $this->source); })()), false, true);
            echo " ";
        }
        echo "at ";
        echo $context["__internal_c100660e1ce6a187b28d0d963856da8925c2f4f210b6c83e5fb12064ced43621"]->macro_method_source_link((isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 235, $this->source); })()));
        echo "</div>
        <code>";
        // line 236
        $this->displayBlock("method_signature", $context, $blocks);
        echo "</code>
    </h3>
    <div class=\"details\">
        ";
        // line 239
        echo $context["__internal_c100660e1ce6a187b28d0d963856da8925c2f4f210b6c83e5fb12064ced43621"]->macro_deprecations((isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 239, $this->source); })()));
        echo "

        ";
        // line 241
        if ((twig_get_attribute($this->env, $this->source, (isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 241, $this->source); })()), "shortdesc", array()) || twig_get_attribute($this->env, $this->source, (isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 241, $this->source); })()), "longdesc", array()))) {
            // line 242
            echo "            <div class=\"method-description\">
                ";
            // line 243
            if (( !twig_get_attribute($this->env, $this->source, (isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 243, $this->source); })()), "shortdesc", array()) &&  !twig_get_attribute($this->env, $this->source, (isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 243, $this->source); })()), "longdesc", array()))) {
                // line 244
                echo "                    <p class=\"no-description\">No description</p>
                ";
            } else {
                // line 246
                echo "                    ";
                if (twig_get_attribute($this->env, $this->source, (isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 246, $this->source); })()), "shortdesc", array())) {
                    // line 247
                    echo "<p>";
                    echo $this->extensions['Sami\Renderer\TwigExtension']->parseDesc($context, twig_get_attribute($this->env, $this->source, (isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 247, $this->source); })()), "shortdesc", array()), (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 247, $this->source); })()));
                    echo "</p>";
                }
                // line 249
                echo "                    ";
                if (twig_get_attribute($this->env, $this->source, (isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 249, $this->source); })()), "longdesc", array())) {
                    // line 250
                    echo "<p>";
                    echo $this->extensions['Sami\Renderer\TwigExtension']->parseDesc($context, twig_get_attribute($this->env, $this->source, (isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 250, $this->source); })()), "longdesc", array()), (isset($context["class"]) || array_key_exists("class", $context) ? $context["class"] : (function () { throw new Twig_Error_Runtime('Variable "class" does not exist.', 250, $this->source); })()));
                    echo "</p>";
                }
            }
            // line 253
            echo "                ";
            if ((twig_get_attribute($this->env, $this->source, (isset($context["project"]) || array_key_exists("project", $context) ? $context["project"] : (function () { throw new Twig_Error_Runtime('Variable "project" does not exist.', 253, $this->source); })()), "config", array(0 => "insert_todos"), "method") == true)) {
                // line 254
                echo "                    ";
                echo $context["__internal_c100660e1ce6a187b28d0d963856da8925c2f4f210b6c83e5fb12064ced43621"]->macro_todos((isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 254, $this->source); })()));
                echo "
                ";
            }
            // line 256
            echo "            </div>
        ";
        }
        // line 258
        echo "        <div class=\"tags\">
            ";
        // line 259
        if (twig_get_attribute($this->env, $this->source, (isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 259, $this->source); })()), "parameters", array())) {
            // line 260
            echo "                <h4>Parameters</h4>

                ";
            // line 262
            $this->displayBlock("parameters", $context, $blocks);
            echo "
            ";
        }
        // line 264
        echo "
            ";
        // line 265
        if ((twig_get_attribute($this->env, $this->source, (isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 265, $this->source); })()), "hintDesc", array()) || twig_get_attribute($this->env, $this->source, (isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 265, $this->source); })()), "hint", array()))) {
            // line 266
            echo "                <h4>Return Value</h4>

                ";
            // line 268
            $this->displayBlock("return", $context, $blocks);
            echo "
            ";
        }
        // line 270
        echo "
            ";
        // line 271
        if (twig_get_attribute($this->env, $this->source, (isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 271, $this->source); })()), "exceptions", array())) {
            // line 272
            echo "                <h4>Exceptions</h4>

                ";
            // line 274
            $this->displayBlock("exceptions", $context, $blocks);
            echo "
            ";
        }
        // line 276
        echo "
            ";
        // line 277
        if (twig_get_attribute($this->env, $this->source, (isset($context["method"]) || array_key_exists("method", $context) ? $context["method"] : (function () { throw new Twig_Error_Runtime('Variable "method" does not exist.', 277, $this->source); })()), "tags", array(0 => "see"), "method")) {
            // line 278
            echo "                <h4>See also</h4>

                ";
            // line 280
            $this->displayBlock("see", $context, $blocks);
            echo "
            ";
        }
        // line 282
        echo "        </div>
    </div>
";
    }

    public function getTemplateName()
    {
        return "class.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  860 => 282,  855 => 280,  851 => 278,  849 => 277,  846 => 276,  841 => 274,  837 => 272,  835 => 271,  832 => 270,  827 => 268,  823 => 266,  821 => 265,  818 => 264,  813 => 262,  809 => 260,  807 => 259,  804 => 258,  800 => 256,  794 => 254,  791 => 253,  785 => 250,  782 => 249,  777 => 247,  774 => 246,  770 => 244,  768 => 243,  765 => 242,  763 => 241,  758 => 239,  752 => 236,  742 => 235,  737 => 234,  734 => 233,  729 => 230,  712 => 227,  709 => 226,  692 => 225,  689 => 224,  686 => 223,  681 => 220,  665 => 217,  660 => 215,  658 => 214,  655 => 212,  650 => 210,  646 => 208,  644 => 207,  636 => 206,  627 => 203,  623 => 201,  606 => 200,  603 => 199,  600 => 198,  595 => 195,  587 => 192,  582 => 190,  580 => 189,  576 => 187,  572 => 186,  566 => 184,  561 => 183,  556 => 182,  552 => 181,  548 => 180,  545 => 179,  541 => 178,  538 => 177,  535 => 176,  530 => 173,  520 => 169,  516 => 168,  511 => 166,  508 => 165,  504 => 164,  501 => 163,  498 => 162,  493 => 159,  484 => 156,  481 => 155,  475 => 153,  469 => 151,  467 => 150,  462 => 149,  460 => 148,  453 => 147,  451 => 146,  447 => 144,  443 => 143,  440 => 142,  437 => 141,  432 => 138,  423 => 135,  419 => 134,  416 => 133,  412 => 132,  409 => 131,  406 => 130,  398 => 125,  394 => 124,  390 => 122,  387 => 121,  382 => 118,  373 => 115,  365 => 114,  359 => 113,  356 => 112,  352 => 111,  349 => 110,  346 => 109,  342 => 106,  338 => 105,  336 => 104,  333 => 103,  327 => 100,  322 => 99,  317 => 98,  312 => 97,  307 => 96,  302 => 95,  298 => 94,  295 => 93,  289 => 90,  272 => 87,  270 => 86,  253 => 85,  250 => 84,  248 => 83,  244 => 81,  242 => 80,  239 => 79,  234 => 78,  230 => 77,  227 => 76,  222 => 73,  217 => 71,  210 => 67,  206 => 65,  204 => 64,  201 => 63,  196 => 61,  192 => 59,  190 => 58,  187 => 57,  182 => 55,  178 => 53,  176 => 52,  173 => 51,  168 => 49,  164 => 47,  162 => 46,  159 => 45,  155 => 43,  149 => 41,  146 => 40,  141 => 38,  138 => 37,  133 => 35,  131 => 34,  128 => 33,  126 => 32,  121 => 30,  116 => 28,  109 => 24,  105 => 23,  100 => 20,  97 => 19,  87 => 13,  85 => 12,  81 => 11,  77 => 9,  74 => 8,  71 => 7,  65 => 5,  59 => 4,  51 => 3,  47 => 1,  45 => 2,  15 => 1,);
    }

    public function getSourceContext()
    {
        return new Twig_Source("{% extends \"layout/layout.twig\" %}
{% from \"macros.twig\" import render_classes, breadcrumbs, namespace_link, class_link, property_link, method_link, hint_link, source_link, method_source_link, deprecated, deprecations, todo, todos %}
{% block title %}{{ class|raw }} | {{ parent() }}{% endblock %}
{% block body_class 'class' %}
{% block page_id 'class:' ~ (class.name|replace({'\\\\': '_'})) %}

{% block below_menu %}
    {% if class.namespace %}
        <div class=\"namespace-breadcrumbs\">
            <ol class=\"breadcrumb\">
                <li><span class=\"label label-default\">{{ class.categoryName|raw }}</span></li>
                {{ breadcrumbs(class.namespace) -}}
                <li>{{ class.shortname|raw }}</li>
            </ol>
        </div>
    {% endif %}
{% endblock %}

{% block page_content %}

    <div class=\"page-header\">
        <h1>
            {{ class.name|split('\\\\')|last|raw }}
            {{ deprecated(class) }}
        </h1>
    </div>

    <p>{{ block('class_signature') }}</p>

    {{ deprecations(class) }}

    {% if class.shortdesc or class.longdesc %}
        <div class=\"description\">
            {% if class.shortdesc -%}
                <p>{{ class.shortdesc|desc(class) }}</p>
            {%- endif %}
            {% if class.longdesc -%}
                <p>{{ class.longdesc|desc(class) }}</p>
            {%- endif %}
            {% if project.config('insert_todos') == true %}
                {{ todos(class) }}
            {% endif %}
        </div>
    {% endif %}

    {% if traits %}
        <h2>Traits</h2>

        {{ render_classes(traits) }}
    {% endif %}

    {% if constants %}
        <h2>Constants</h2>

        {{ block('constants') }}
    {% endif %}

    {% if properties %}
        <h2>Properties</h2>

        {{ block('properties') }}
    {% endif %}

    {% if methods %}
        <h2>Methods</h2>

        {{ block('methods') }}

        <h2>Details</h2>

        {{ block('methods_details') }}
    {% endif %}

{% endblock %}

{% block class_signature -%}
    {% if not class.interface and class.abstract %}abstract {% endif %}
    {{ class.categoryName|raw }}
    <strong>{{ class.shortname|raw }}</strong>
    {%- if class.parent %}
        extends {{ class_link(class.parent) }}
    {%- endif %}
    {%- if class.interfaces|length > 0 %}
        implements
        {% for interface in class.interfaces %}
            {{- class_link(interface) }}
            {%- if not loop.last %}, {% endif %}
        {%- endfor %}
    {%- endif %}
    {{- source_link(project, class) }}
{% endblock %}

{% block method_signature -%}
    {% if method.final %}final{% endif %}
    {% if method.abstract %}abstract{% endif %}
    {% if method.static %}static{% endif %}
    {% if method.protected %}protected{% endif %}
    {% if method.private %}private{% endif %}
    {{ hint_link(method.hint) }}
    <strong>{{ method.name|raw }}</strong>{{ block('method_parameters_signature') }}
{%- endblock %}

{% block method_parameters_signature -%}
    {%- from \"macros.twig\" import method_parameters_signature -%}
    {{ method_parameters_signature(method) }}
    {{ deprecated(method) }}
{%- endblock %}

{% block parameters %}
    <table class=\"table table-condensed\">
        {% for parameter in method.parameters %}
            <tr>
                <td>{% if parameter.hint %}{{ hint_link(parameter.hint) }}{% endif %}</td>
                <td>{%- if parameter.variadic %}...{% endif %}\${{ parameter.name|raw }}</td>
                <td>{{ parameter.shortdesc|desc(class) }}</td>
            </tr>
        {% endfor %}
    </table>
{% endblock %}

{% block return %}
    <table class=\"table table-condensed\">
        <tr>
            <td>{{ hint_link(method.hint) }}</td>
            <td>{{ method.hintDesc|desc(class) }}</td>
        </tr>
    </table>
{% endblock %}

{% block exceptions %}
    <table class=\"table table-condensed\">
        {% for exception in method.exceptions %}
            <tr>
                <td>{{ class_link(exception[0]) }}</td>
                <td>{{ exception[1]|desc(class) }}</td>
            </tr>
        {% endfor %}
    </table>
{% endblock %}

{% block see %}
    <table class=\"table table-condensed\">
        {% for see in method.see %}
            <tr>
                <td>
                    {% if see[4] %}
                        <a href=\"{{see[4]}}\">{{see[4]}}</a>
                    {% elseif see[3] %}
                        {{ method_link(see[3], false, false) }}
                    {% elseif see[2] %}
                        {{ class_link(see[2]) }}
                    {% else %}
                        {{ see[0]|raw }}
                    {% endif %}
                </td>
                <td>{{ see[1]|raw }}</td>
            </tr>
        {% endfor %}
    </table>
{% endblock %}

{% block constants %}
    <table class=\"table table-condensed\">
        {% for constant in constants %}
            <tr>
                <td>{{ constant.name|raw }}</td>
                <td class=\"last\">
                    <p><em>{{ constant.shortdesc|desc(class) }}</em></p>
                    <p>{{ constant.longdesc|desc(class) }}</p>
                </td>
            </tr>
        {% endfor %}
    </table>
{% endblock %}

{% block properties %}
    <table class=\"table table-condensed\">
        {% for property in properties %}
            <tr>
                <td class=\"type\" id=\"property_{{ property.name|raw }}\">
                    {% if property.static %}static{% endif %}
                    {% if property.protected %}protected{% endif %}
                    {% if property.private %}private{% endif %}
                    {{ hint_link(property.hint) }}
                </td>
                <td>\${{ property.name|raw }}</td>
                <td class=\"last\">{{ property.shortdesc|desc(class) }}</td>
                <td>
                    {%- if property.class is not same as(class) -%}
                        <small>from&nbsp;{{ property_link(property, false, true) }}</small>
                    {%- endif -%}
                </td>
            </tr>
        {% endfor %}
    </table>
{% endblock %}

{% block methods %}
    <div class=\"container-fluid underlined\">
        {% for method in methods %}
            <div class=\"row\">
                <div class=\"col-md-2 type\">
                    {% if method.static %}static&nbsp;{% endif %}{{ hint_link(method.hint) }}
                </div>
                <div class=\"col-md-8 type\">
                    <a href=\"#method_{{ method.name|raw }}\">{{ method.name|raw }}</a>{{ block('method_parameters_signature') }}
                    {% if not method.shortdesc %}
                        <p class=\"no-description\">No description</p>
                    {% else %}
                        <p>{{ method.shortdesc|desc(class) }}</p>
                    {%- endif %}
                </div>
                <div class=\"col-md-2\">
                    {%- if method.class is not same as(class) -%}
                        <small>from&nbsp;{{ method_link(method, false, true) }}</small>
                    {%- endif -%}
                </div>
            </div>
        {% endfor %}
    </div>
{% endblock %}

{% block methods_details %}
    <div id=\"method-details\">
        {% for method in methods %}
            <div class=\"method-item\">
                {{ block('method') }}
            </div>
        {% endfor %}
    </div>
{% endblock %}

{% block method %}
    <h3 id=\"method_{{ method.name|raw }}\">
        <div class=\"location\">{% if method.class is not same as(class) %}in {{ method_link(method, false, true) }} {% endif %}at {{ method_source_link(method) }}</div>
        <code>{{ block('method_signature') }}</code>
    </h3>
    <div class=\"details\">
        {{ deprecations(method) }}

        {% if method.shortdesc or method.longdesc %}
            <div class=\"method-description\">
                {% if not method.shortdesc and not method.longdesc %}
                    <p class=\"no-description\">No description</p>
                {% else %}
                    {% if method.shortdesc -%}
                    <p>{{ method.shortdesc|desc(class) }}</p>
                    {%- endif %}
                    {% if method.longdesc -%}
                    <p>{{ method.longdesc|desc(class) }}</p>
                    {%- endif %}
                {%- endif %}
                {% if project.config('insert_todos') == true %}
                    {{ todos(method) }}
                {% endif %}
            </div>
        {% endif %}
        <div class=\"tags\">
            {% if method.parameters %}
                <h4>Parameters</h4>

                {{ block('parameters') }}
            {% endif %}

            {% if method.hintDesc or method.hint %}
                <h4>Return Value</h4>

                {{ block('return') }}
            {% endif %}

            {% if method.exceptions %}
                <h4>Exceptions</h4>

                {{ block('exceptions') }}
            {% endif %}

            {% if method.tags('see') %}
                <h4>See also</h4>

                {{ block('see') }}
            {% endif %}
        </div>
    </div>
{% endblock %}
", "class.twig", "phar://C:/sami/sami.phar/Sami/Resources/themes\\default/class.twig");
    }
}
