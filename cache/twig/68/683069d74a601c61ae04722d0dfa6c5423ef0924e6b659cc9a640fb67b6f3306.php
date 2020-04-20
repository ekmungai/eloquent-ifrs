<?php

/* namespace.twig */
class __TwigTemplate_c85567a8cbb74e4ce4fb4bfaa2cc978230cc646cb5f0b0c5e3c39c7e9e1d9a94 extends Twig_Template
{
    private $source;

    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        // line 1
        $this->parent = $this->loadTemplate("layout/layout.twig", "namespace.twig", 1);
        $this->blocks = array(
            'title' => array($this, 'block_title'),
            'body_class' => array($this, 'block_body_class'),
            'page_id' => array($this, 'block_page_id'),
            'below_menu' => array($this, 'block_below_menu'),
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
        $context["__internal_9d65f06ca1cd2f491b7687d17e20dfe726814c2d2c295048bba4146be77b95fe"] = $this->loadTemplate("macros.twig", "namespace.twig", 2);
        // line 1
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_title($context, array $blocks = array())
    {
        echo (isset($context["namespace"]) || array_key_exists("namespace", $context) ? $context["namespace"] : (function () { throw new Twig_Error_Runtime('Variable "namespace" does not exist.', 3, $this->source); })());
        echo " | ";
        $this->displayParentBlock("title", $context, $blocks);
    }

    // line 4
    public function block_body_class($context, array $blocks = array())
    {
        echo "namespace";
    }

    // line 5
    public function block_page_id($context, array $blocks = array())
    {
        echo twig_escape_filter($this->env, ("namespace:" . twig_replace_filter((isset($context["namespace"]) || array_key_exists("namespace", $context) ? $context["namespace"] : (function () { throw new Twig_Error_Runtime('Variable "namespace" does not exist.', 5, $this->source); })()), array("\\" => "_"))), "html", null, true);
    }

    // line 7
    public function block_below_menu($context, array $blocks = array())
    {
        // line 8
        echo "    <div class=\"namespace-breadcrumbs\">
        <ol class=\"breadcrumb\">
            <li><span class=\"label label-default\">Namespace</span></li>
            ";
        // line 11
        echo $context["__internal_9d65f06ca1cd2f491b7687d17e20dfe726814c2d2c295048bba4146be77b95fe"]->macro_breadcrumbs((isset($context["namespace"]) || array_key_exists("namespace", $context) ? $context["namespace"] : (function () { throw new Twig_Error_Runtime('Variable "namespace" does not exist.', 11, $this->source); })()));
        echo "
        </ol>
    </div>
";
    }

    // line 16
    public function block_page_content($context, array $blocks = array())
    {
        // line 17
        echo "
    <div class=\"page-header\">
        <h1>";
        // line 19
        echo (isset($context["namespace"]) || array_key_exists("namespace", $context) ? $context["namespace"] : (function () { throw new Twig_Error_Runtime('Variable "namespace" does not exist.', 19, $this->source); })());
        echo "</h1>
    </div>

    ";
        // line 22
        if ((isset($context["subnamespaces"]) || array_key_exists("subnamespaces", $context) ? $context["subnamespaces"] : (function () { throw new Twig_Error_Runtime('Variable "subnamespaces" does not exist.', 22, $this->source); })())) {
            // line 23
            echo "        <h2>Namespaces</h2>
        <div class=\"namespace-list\">
            ";
            // line 25
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable((isset($context["subnamespaces"]) || array_key_exists("subnamespaces", $context) ? $context["subnamespaces"] : (function () { throw new Twig_Error_Runtime('Variable "subnamespaces" does not exist.', 25, $this->source); })()));
            foreach ($context['_seq'] as $context["_key"] => $context["ns"]) {
                echo $context["__internal_9d65f06ca1cd2f491b7687d17e20dfe726814c2d2c295048bba4146be77b95fe"]->macro_namespace_link($context["ns"]);
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['ns'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 26
            echo "        </div>
    ";
        }
        // line 28
        echo "
    ";
        // line 29
        if ((isset($context["classes"]) || array_key_exists("classes", $context) ? $context["classes"] : (function () { throw new Twig_Error_Runtime('Variable "classes" does not exist.', 29, $this->source); })())) {
            // line 30
            echo "        <h2>Classes</h2>
        ";
            // line 31
            echo $context["__internal_9d65f06ca1cd2f491b7687d17e20dfe726814c2d2c295048bba4146be77b95fe"]->macro_render_classes((isset($context["classes"]) || array_key_exists("classes", $context) ? $context["classes"] : (function () { throw new Twig_Error_Runtime('Variable "classes" does not exist.', 31, $this->source); })()));
            echo "
    ";
        }
        // line 33
        echo "
    ";
        // line 34
        if ((isset($context["interfaces"]) || array_key_exists("interfaces", $context) ? $context["interfaces"] : (function () { throw new Twig_Error_Runtime('Variable "interfaces" does not exist.', 34, $this->source); })())) {
            // line 35
            echo "        <h2>Interfaces</h2>
        ";
            // line 36
            echo $context["__internal_9d65f06ca1cd2f491b7687d17e20dfe726814c2d2c295048bba4146be77b95fe"]->macro_render_classes((isset($context["interfaces"]) || array_key_exists("interfaces", $context) ? $context["interfaces"] : (function () { throw new Twig_Error_Runtime('Variable "interfaces" does not exist.', 36, $this->source); })()));
            echo "
    ";
        }
        // line 38
        echo "
    ";
        // line 39
        if ((isset($context["exceptions"]) || array_key_exists("exceptions", $context) ? $context["exceptions"] : (function () { throw new Twig_Error_Runtime('Variable "exceptions" does not exist.', 39, $this->source); })())) {
            // line 40
            echo "        <h2>Exceptions</h2>
        ";
            // line 41
            echo $context["__internal_9d65f06ca1cd2f491b7687d17e20dfe726814c2d2c295048bba4146be77b95fe"]->macro_render_classes((isset($context["exceptions"]) || array_key_exists("exceptions", $context) ? $context["exceptions"] : (function () { throw new Twig_Error_Runtime('Variable "exceptions" does not exist.', 41, $this->source); })()));
            echo "
    ";
        }
        // line 43
        echo "
";
    }

    public function getTemplateName()
    {
        return "namespace.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  146 => 43,  141 => 41,  138 => 40,  136 => 39,  133 => 38,  128 => 36,  125 => 35,  123 => 34,  120 => 33,  115 => 31,  112 => 30,  110 => 29,  107 => 28,  103 => 26,  94 => 25,  90 => 23,  88 => 22,  82 => 19,  78 => 17,  75 => 16,  67 => 11,  62 => 8,  59 => 7,  53 => 5,  47 => 4,  39 => 3,  35 => 1,  33 => 2,  15 => 1,);
    }

    public function getSourceContext()
    {
        return new Twig_Source("{% extends \"layout/layout.twig\" %}
{% from \"macros.twig\" import breadcrumbs, render_classes, class_link, namespace_link %}
{% block title %}{{ namespace|raw }} | {{ parent() }}{% endblock %}
{% block body_class 'namespace' %}
{% block page_id 'namespace:' ~ (namespace|replace({'\\\\': '_'})) %}

{% block below_menu %}
    <div class=\"namespace-breadcrumbs\">
        <ol class=\"breadcrumb\">
            <li><span class=\"label label-default\">Namespace</span></li>
            {{ breadcrumbs(namespace) }}
        </ol>
    </div>
{% endblock %}

{% block page_content %}

    <div class=\"page-header\">
        <h1>{{ namespace|raw }}</h1>
    </div>

    {% if subnamespaces %}
        <h2>Namespaces</h2>
        <div class=\"namespace-list\">
            {% for ns in subnamespaces %}{{ namespace_link(ns) }}{% endfor %}
        </div>
    {% endif %}

    {% if classes %}
        <h2>Classes</h2>
        {{ render_classes(classes) }}
    {% endif %}

    {% if interfaces %}
        <h2>Interfaces</h2>
        {{ render_classes(interfaces) }}
    {% endif %}

    {% if exceptions %}
        <h2>Exceptions</h2>
        {{ render_classes(exceptions) }}
    {% endif %}

{% endblock %}
", "namespace.twig", "phar://C:/sami/sami.phar/Sami/Resources/themes\\default/namespace.twig");
    }
}
