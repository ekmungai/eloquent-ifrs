<?php

/* layout/base.twig */
class __TwigTemplate_946e2457f21b3ae2a52080694b86cdfc21b07a263f2ec75bae108b506d4c39b7 extends Twig_Template
{
    private $source;

    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = array(
            'title' => array($this, 'block_title'),
            'head' => array($this, 'block_head'),
            'html' => array($this, 'block_html'),
            'body_class' => array($this, 'block_body_class'),
            'page_id' => array($this, 'block_page_id'),
            'content' => array($this, 'block_content'),
        );
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        // line 1
        echo "<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\" />
    <meta name=\"robots\" content=\"index, follow, all\" />
    <title>";
        // line 6
        $this->displayBlock('title', $context, $blocks);
        echo "</title>

    ";
        // line 8
        $this->displayBlock('head', $context, $blocks);
        // line 20
        echo "
    ";
        // line 21
        if (twig_get_attribute($this->env, $this->source, (isset($context["project"]) || array_key_exists("project", $context) ? $context["project"] : (function () { throw new Twig_Error_Runtime('Variable "project" does not exist.', 21, $this->source); })()), "config", array(0 => "favicon"), "method")) {
            // line 22
            echo "        <link rel=\"shortcut icon\" href=\"";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, (isset($context["project"]) || array_key_exists("project", $context) ? $context["project"] : (function () { throw new Twig_Error_Runtime('Variable "project" does not exist.', 22, $this->source); })()), "config", array(0 => "favicon"), "method"), "html", null, true);
            echo "\" />
    ";
        }
        // line 24
        echo "
    ";
        // line 25
        if (twig_get_attribute($this->env, $this->source, (isset($context["project"]) || array_key_exists("project", $context) ? $context["project"] : (function () { throw new Twig_Error_Runtime('Variable "project" does not exist.', 25, $this->source); })()), "config", array(0 => "base_url"), "method")) {
            // line 26
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(twig_get_attribute($this->env, $this->source, (isset($context["project"]) || array_key_exists("project", $context) ? $context["project"] : (function () { throw new Twig_Error_Runtime('Variable "project" does not exist.', 26, $this->source); })()), "versions", array()));
            foreach ($context['_seq'] as $context["_key"] => $context["version"]) {
                // line 27
                echo "<link rel=\"search\"
                  type=\"application/opensearchdescription+xml\"
                  href=\"";
                // line 29
                echo twig_escape_filter($this->env, twig_replace_filter(twig_get_attribute($this->env, $this->source, (isset($context["project"]) || array_key_exists("project", $context) ? $context["project"] : (function () { throw new Twig_Error_Runtime('Variable "project" does not exist.', 29, $this->source); })()), "config", array(0 => "base_url"), "method"), array("%version%" => $context["version"])), "html", null, true);
                echo "/opensearch.xml\"
                  title=\"";
                // line 30
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, (isset($context["project"]) || array_key_exists("project", $context) ? $context["project"] : (function () { throw new Twig_Error_Runtime('Variable "project" does not exist.', 30, $this->source); })()), "config", array(0 => "title"), "method"), "html", null, true);
                echo " (";
                echo twig_escape_filter($this->env, $context["version"], "html", null, true);
                echo ")\" />
        ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['version'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
        }
        // line 33
        echo "</head>

";
        // line 35
        $this->displayBlock('html', $context, $blocks);
        // line 40
        echo "
</html>
";
    }

    // line 6
    public function block_title($context, array $blocks = array())
    {
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, (isset($context["project"]) || array_key_exists("project", $context) ? $context["project"] : (function () { throw new Twig_Error_Runtime('Variable "project" does not exist.', 6, $this->source); })()), "config", array(0 => "title"), "method"), "html", null, true);
    }

    // line 8
    public function block_head($context, array $blocks = array())
    {
        // line 9
        echo "        <link rel=\"stylesheet\" type=\"text/css\" href=\"";
        echo twig_escape_filter($this->env, $this->extensions['Sami\Renderer\TwigExtension']->pathForStaticFile($context, "css/bootstrap.min.css"), "html", null, true);
        echo "\">
        <link rel=\"stylesheet\" type=\"text/css\" href=\"";
        // line 10
        echo twig_escape_filter($this->env, $this->extensions['Sami\Renderer\TwigExtension']->pathForStaticFile($context, "css/bootstrap-theme.min.css"), "html", null, true);
        echo "\">
        <link rel=\"stylesheet\" type=\"text/css\" href=\"";
        // line 11
        echo twig_escape_filter($this->env, $this->extensions['Sami\Renderer\TwigExtension']->pathForStaticFile($context, "css/sami.css"), "html", null, true);
        echo "\">
        <script src=\"";
        // line 12
        echo twig_escape_filter($this->env, $this->extensions['Sami\Renderer\TwigExtension']->pathForStaticFile($context, "js/jquery-1.11.1.min.js"), "html", null, true);
        echo "\"></script>
        <script src=\"";
        // line 13
        echo twig_escape_filter($this->env, $this->extensions['Sami\Renderer\TwigExtension']->pathForStaticFile($context, "js/bootstrap.min.js"), "html", null, true);
        echo "\"></script>
        <script src=\"";
        // line 14
        echo twig_escape_filter($this->env, $this->extensions['Sami\Renderer\TwigExtension']->pathForStaticFile($context, "js/typeahead.min.js"), "html", null, true);
        echo "\"></script>
        <script src=\"";
        // line 15
        echo twig_escape_filter($this->env, $this->extensions['Sami\Renderer\TwigExtension']->pathForStaticFile($context, "sami.js"), "html", null, true);
        echo "\"></script>
        <meta name=\"MobileOptimized\" content=\"width\">
        <meta name=\"HandheldFriendly\" content=\"true\">
        <meta name=\"viewport\" content=\"width=device-width,initial-scale=1,maximum-scale=1\">
    ";
    }

    // line 35
    public function block_html($context, array $blocks = array())
    {
        // line 36
        echo "    <body id=\"";
        $this->displayBlock('body_class', $context, $blocks);
        echo "\" data-name=\"";
        $this->displayBlock('page_id', $context, $blocks);
        echo "\" data-root-path=\"";
        echo twig_escape_filter($this->env, (isset($context["root_path"]) || array_key_exists("root_path", $context) ? $context["root_path"] : (function () { throw new Twig_Error_Runtime('Variable "root_path" does not exist.', 36, $this->source); })()), "html", null, true);
        echo "\">
        ";
        // line 37
        $this->displayBlock('content', $context, $blocks);
        // line 38
        echo "    </body>
";
    }

    // line 36
    public function block_body_class($context, array $blocks = array())
    {
        echo "";
    }

    public function block_page_id($context, array $blocks = array())
    {
        echo "";
    }

    // line 37
    public function block_content($context, array $blocks = array())
    {
        echo "";
    }

    public function getTemplateName()
    {
        return "layout/base.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  167 => 37,  156 => 36,  151 => 38,  149 => 37,  140 => 36,  137 => 35,  128 => 15,  124 => 14,  120 => 13,  116 => 12,  112 => 11,  108 => 10,  103 => 9,  100 => 8,  94 => 6,  88 => 40,  86 => 35,  82 => 33,  71 => 30,  67 => 29,  63 => 27,  59 => 26,  57 => 25,  54 => 24,  48 => 22,  46 => 21,  43 => 20,  41 => 8,  36 => 6,  29 => 1,);
    }

    public function getSourceContext()
    {
        return new Twig_Source("<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\" />
    <meta name=\"robots\" content=\"index, follow, all\" />
    <title>{% block title project.config('title') %}</title>

    {% block head %}
        <link rel=\"stylesheet\" type=\"text/css\" href=\"{{ path('css/bootstrap.min.css') }}\">
        <link rel=\"stylesheet\" type=\"text/css\" href=\"{{ path('css/bootstrap-theme.min.css') }}\">
        <link rel=\"stylesheet\" type=\"text/css\" href=\"{{ path('css/sami.css') }}\">
        <script src=\"{{ path('js/jquery-1.11.1.min.js') }}\"></script>
        <script src=\"{{ path('js/bootstrap.min.js') }}\"></script>
        <script src=\"{{ path('js/typeahead.min.js') }}\"></script>
        <script src=\"{{ path('sami.js') }}\"></script>
        <meta name=\"MobileOptimized\" content=\"width\">
        <meta name=\"HandheldFriendly\" content=\"true\">
        <meta name=\"viewport\" content=\"width=device-width,initial-scale=1,maximum-scale=1\">
    {% endblock %}

    {% if project.config('favicon') %}
        <link rel=\"shortcut icon\" href=\"{{ project.config('favicon') }}\" />
    {% endif %}

    {% if project.config('base_url') %}
        {%- for version in project.versions -%}
            <link rel=\"search\"
                  type=\"application/opensearchdescription+xml\"
                  href=\"{{ project.config('base_url')|replace({'%version%': version}) }}/opensearch.xml\"
                  title=\"{{ project.config('title') }} ({{ version }})\" />
        {% endfor -%}
    {% endif %}
</head>

{% block html %}
    <body id=\"{% block body_class '' %}\" data-name=\"{% block page_id '' %}\" data-root-path=\"{{ root_path }}\">
        {% block content '' %}
    </body>
{% endblock %}

</html>
", "layout/base.twig", "phar://C:/sami/sami.phar/Sami/Resources/themes\\default/layout/base.twig");
    }
}
