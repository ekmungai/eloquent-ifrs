<?php

/* interfaces.twig */
class __TwigTemplate_37fa428ea3475446a383f344e07775f07ce65de0f3366132853e5f653089b267 extends Twig_Template
{
    private $source;

    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        // line 1
        $this->parent = $this->loadTemplate("layout/layout.twig", "interfaces.twig", 1);
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
        $context["__internal_9fcd207fbe0fd695fbe43de59bd379479619eaef3cc04df9a8e69a901bdcd6d8"] = $this->loadTemplate("macros.twig", "interfaces.twig", 2);
        // line 1
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_title($context, array $blocks = array())
    {
        echo "Interfaces | ";
        $this->displayParentBlock("title", $context, $blocks);
    }

    // line 4
    public function block_body_class($context, array $blocks = array())
    {
        echo "interfaces";
    }

    // line 6
    public function block_page_content($context, array $blocks = array())
    {
        // line 7
        echo "    <div class=\"page-header\">
        <h1>Interfaces</h1>
    </div>

    ";
        // line 11
        echo $context["__internal_9fcd207fbe0fd695fbe43de59bd379479619eaef3cc04df9a8e69a901bdcd6d8"]->macro_render_classes((isset($context["interfaces"]) || array_key_exists("interfaces", $context) ? $context["interfaces"] : (function () { throw new Twig_Error_Runtime('Variable "interfaces" does not exist.', 11, $this->source); })()));
        echo "
";
    }

    public function getTemplateName()
    {
        return "interfaces.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  59 => 11,  53 => 7,  50 => 6,  44 => 4,  37 => 3,  33 => 1,  31 => 2,  15 => 1,);
    }

    public function getSourceContext()
    {
        return new Twig_Source("{% extends \"layout/layout.twig\" %}
{% from \"macros.twig\" import render_classes %}
{% block title %}Interfaces | {{ parent() }}{% endblock %}
{% block body_class 'interfaces' %}

{% block page_content %}
    <div class=\"page-header\">
        <h1>Interfaces</h1>
    </div>

    {{ render_classes(interfaces) }}
{% endblock %}
", "interfaces.twig", "phar://C:/sami/sami.phar/Sami/Resources/themes\\default/interfaces.twig");
    }
}
