<?php

/* doc-index.twig */
class __TwigTemplate_b47dea9bcb8e74771a462bb2c69652c197f94fbc5ca82790da5e36f84ab81209 extends Twig_Template
{
    private $source;

    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        // line 1
        $this->parent = $this->loadTemplate("layout/layout.twig", "doc-index.twig", 1);
        $this->blocks = array(
            'title' => array($this, 'block_title'),
            'body_class' => array($this, 'block_body_class'),
            'page_content' => array($this, 'block_page_content'),
        );
    }

    protected function doGetParent(array $context)
    {
        return "layout/layout.twig";
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        // line 2
        $context["__internal_93a9144c61a1671064ad8c5c122b24d5b20cdad4b2f1300891851edb77e6e9f7"] = $this->loadTemplate("macros.twig", "doc-index.twig", 2);
        // line 1
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_title($context, array $blocks = array())
    {
        echo "Index | ";
        $this->displayParentBlock("title", $context, $blocks);
    }

    // line 4
    public function block_body_class($context, array $blocks = array())
    {
        echo "doc-index";
    }

    // line 6
    public function block_page_content($context, array $blocks = array())
    {
        // line 7
        echo "
    <div class=\"page-header\">
        <h1>Index</h1>
    </div>

    <ul class=\"pagination\">
        ";
        // line 13
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(range("A", "Z"));
        foreach ($context['_seq'] as $context["_key"] => $context["letter"]) {
            // line 14
            echo "            ";
            if ((twig_get_attribute($this->env, $this->source, ($context["items"] ?? null), $context["letter"], array(), "array", true, true) && (twig_length_filter($this->env, twig_get_attribute($this->env, $this->source, (isset($context["items"]) || array_key_exists("items", $context) ? $context["items"] : (function () { throw new Twig_Error_Runtime('Variable "items" does not exist.', 14, $this->source); })()), $context["letter"], array(), "array")) > 1))) {
                // line 15
                echo "                <li><a href=\"#letter";
                echo $context["letter"];
                echo "\">";
                echo $context["letter"];
                echo "</a></li>
            ";
            } else {
                // line 17
                echo "                <li class=\"disabled\"><a href=\"#letter";
                echo $context["letter"];
                echo "\">";
                echo $context["letter"];
                echo "</a></li>
            ";
            }
            // line 19
            echo "        ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['letter'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 20
        echo "    </ul>

    ";
        // line 22
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable((isset($context["items"]) || array_key_exists("items", $context) ? $context["items"] : (function () { throw new Twig_Error_Runtime('Variable "items" does not exist.', 22, $this->source); })()));
        foreach ($context['_seq'] as $context["letter"] => $context["elements"]) {
            // line 23
            echo "<h2 id=\"letter";
            echo $context["letter"];
            echo "\">";
            echo $context["letter"];
            echo "</h2>
        <dl id=\"index\">";
            // line 25
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable($context["elements"]);
            foreach ($context['_seq'] as $context["_key"] => $context["element"]) {
                // line 26
                $context["type"] = twig_get_attribute($this->env, $this->source, $context["element"], 0, array(), "array");
                // line 27
                $context["value"] = twig_get_attribute($this->env, $this->source, $context["element"], 1, array(), "array");
                // line 28
                if (("class" == (isset($context["type"]) || array_key_exists("type", $context) ? $context["type"] : (function () { throw new Twig_Error_Runtime('Variable "type" does not exist.', 28, $this->source); })()))) {
                    // line 29
                    echo "<dt>";
                    echo $context["__internal_93a9144c61a1671064ad8c5c122b24d5b20cdad4b2f1300891851edb77e6e9f7"]->macro_class_link((isset($context["value"]) || array_key_exists("value", $context) ? $context["value"] : (function () { throw new Twig_Error_Runtime('Variable "value" does not exist.', 29, $this->source); })()));
                    if ((isset($context["has_namespaces"]) || array_key_exists("has_namespaces", $context) ? $context["has_namespaces"] : (function () { throw new Twig_Error_Runtime('Variable "has_namespaces" does not exist.', 29, $this->source); })())) {
                        echo " &mdash; <em>Class in namespace ";
                        echo $context["__internal_93a9144c61a1671064ad8c5c122b24d5b20cdad4b2f1300891851edb77e6e9f7"]->macro_namespace_link(twig_get_attribute($this->env, $this->source, (isset($context["value"]) || array_key_exists("value", $context) ? $context["value"] : (function () { throw new Twig_Error_Runtime('Variable "value" does not exist.', 29, $this->source); })()), "namespace", array()));
                    }
                    echo "</em></dt>
                    <dd>";
                    // line 30
                    echo $this->extensions['Sami\Renderer\TwigExtension']->parseDesc($context, twig_get_attribute($this->env, $this->source, (isset($context["value"]) || array_key_exists("value", $context) ? $context["value"] : (function () { throw new Twig_Error_Runtime('Variable "value" does not exist.', 30, $this->source); })()), "shortdesc", array()), (isset($context["value"]) || array_key_exists("value", $context) ? $context["value"] : (function () { throw new Twig_Error_Runtime('Variable "value" does not exist.', 30, $this->source); })()));
                    echo "</dd>";
                } elseif (("method" ==                 // line 31
(isset($context["type"]) || array_key_exists("type", $context) ? $context["type"] : (function () { throw new Twig_Error_Runtime('Variable "type" does not exist.', 31, $this->source); })()))) {
                    // line 32
                    echo "<dt>";
                    echo $context["__internal_93a9144c61a1671064ad8c5c122b24d5b20cdad4b2f1300891851edb77e6e9f7"]->macro_method_link((isset($context["value"]) || array_key_exists("value", $context) ? $context["value"] : (function () { throw new Twig_Error_Runtime('Variable "value" does not exist.', 32, $this->source); })()));
                    echo "() &mdash; <em>Method in class ";
                    echo $context["__internal_93a9144c61a1671064ad8c5c122b24d5b20cdad4b2f1300891851edb77e6e9f7"]->macro_class_link(twig_get_attribute($this->env, $this->source, (isset($context["value"]) || array_key_exists("value", $context) ? $context["value"] : (function () { throw new Twig_Error_Runtime('Variable "value" does not exist.', 32, $this->source); })()), "class", array()));
                    echo "</em></dt>
                    <dd>";
                    // line 33
                    echo $this->extensions['Sami\Renderer\TwigExtension']->parseDesc($context, twig_get_attribute($this->env, $this->source, (isset($context["value"]) || array_key_exists("value", $context) ? $context["value"] : (function () { throw new Twig_Error_Runtime('Variable "value" does not exist.', 33, $this->source); })()), "shortdesc", array()), twig_get_attribute($this->env, $this->source, (isset($context["value"]) || array_key_exists("value", $context) ? $context["value"] : (function () { throw new Twig_Error_Runtime('Variable "value" does not exist.', 33, $this->source); })()), "class", array()));
                    echo "</dd>";
                } elseif (("property" ==                 // line 34
(isset($context["type"]) || array_key_exists("type", $context) ? $context["type"] : (function () { throw new Twig_Error_Runtime('Variable "type" does not exist.', 34, $this->source); })()))) {
                    // line 35
                    echo "<dt>\$";
                    echo $context["__internal_93a9144c61a1671064ad8c5c122b24d5b20cdad4b2f1300891851edb77e6e9f7"]->macro_property_link((isset($context["value"]) || array_key_exists("value", $context) ? $context["value"] : (function () { throw new Twig_Error_Runtime('Variable "value" does not exist.', 35, $this->source); })()));
                    echo " &mdash; <em>Property in class ";
                    echo $context["__internal_93a9144c61a1671064ad8c5c122b24d5b20cdad4b2f1300891851edb77e6e9f7"]->macro_class_link(twig_get_attribute($this->env, $this->source, (isset($context["value"]) || array_key_exists("value", $context) ? $context["value"] : (function () { throw new Twig_Error_Runtime('Variable "value" does not exist.', 35, $this->source); })()), "class", array()));
                    echo "</em></dt>
                    <dd>";
                    // line 36
                    echo $this->extensions['Sami\Renderer\TwigExtension']->parseDesc($context, twig_get_attribute($this->env, $this->source, (isset($context["value"]) || array_key_exists("value", $context) ? $context["value"] : (function () { throw new Twig_Error_Runtime('Variable "value" does not exist.', 36, $this->source); })()), "shortdesc", array()), twig_get_attribute($this->env, $this->source, (isset($context["value"]) || array_key_exists("value", $context) ? $context["value"] : (function () { throw new Twig_Error_Runtime('Variable "value" does not exist.', 36, $this->source); })()), "class", array()));
                    echo "</dd>";
                }
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['element'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 39
            echo "        </dl>";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['letter'], $context['elements'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
    }

    public function getTemplateName()
    {
        return "doc-index.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  156 => 39,  148 => 36,  141 => 35,  139 => 34,  136 => 33,  129 => 32,  127 => 31,  124 => 30,  115 => 29,  113 => 28,  111 => 27,  109 => 26,  105 => 25,  98 => 23,  94 => 22,  90 => 20,  84 => 19,  76 => 17,  68 => 15,  65 => 14,  61 => 13,  53 => 7,  50 => 6,  44 => 4,  37 => 3,  33 => 1,  31 => 2,  15 => 1,);
    }

    public function getSourceContext()
    {
        return new Twig_Source("{% extends \"layout/layout.twig\" %}
{% from \"macros.twig\" import class_link, namespace_link, method_link, property_link %}
{% block title %}Index | {{ parent() }}{% endblock %}
{% block body_class 'doc-index' %}

{% block page_content %}

    <div class=\"page-header\">
        <h1>Index</h1>
    </div>

    <ul class=\"pagination\">
        {% for letter in 'A'..'Z' %}
            {% if items[letter] is defined and items[letter]|length > 1 %}
                <li><a href=\"#letter{{ letter|raw }}\">{{ letter|raw }}</a></li>
            {% else %}
                <li class=\"disabled\"><a href=\"#letter{{ letter|raw }}\">{{ letter|raw }}</a></li>
            {% endif %}
        {% endfor %}
    </ul>

    {% for letter, elements in items -%}
        <h2 id=\"letter{{ letter|raw }}\">{{ letter|raw }}</h2>
        <dl id=\"index\">
            {%- for element in elements %}
                {%- set type = element[0] %}
                {%- set value = element[1] %}
                {%- if 'class' == type -%}
                    <dt>{{ class_link(value) }}{% if has_namespaces %} &mdash; <em>Class in namespace {{ namespace_link(value.namespace) }}{% endif %}</em></dt>
                    <dd>{{ value.shortdesc|desc(value) }}</dd>
                {%- elseif 'method' == type -%}
                    <dt>{{ method_link(value) }}() &mdash; <em>Method in class {{ class_link(value.class) }}</em></dt>
                    <dd>{{ value.shortdesc|desc(value.class) }}</dd>
                {%- elseif 'property' == type -%}
                    <dt>\${{ property_link(value) }} &mdash; <em>Property in class {{ class_link(value.class) }}</em></dt>
                    <dd>{{ value.shortdesc|desc(value.class) }}</dd>
                {%- endif %}
            {%- endfor %}
        </dl>
    {%- endfor %}
{% endblock %}
", "doc-index.twig", "phar://C:/sami/sami.phar/Sami/Resources/themes\\default/doc-index.twig");
    }
}
