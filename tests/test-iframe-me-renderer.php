<?php

class Test_Iframe_Me_Rendered extends WP_UnitTestCase
{

    public function setUp(): void
    {
        add_filter('pre_http_request', function () {
            return [];
        });
    }

    public function test_iframe_me_empty_url_init()
    {
        $this->expectException(Iframe_Me_Exception::class);
        new Iframe_Me_Renderer('');
    }

    public function test_iframe_me_successful_init()
    {
        $renderer = new Iframe_Me_Renderer('https://localhost');
        $this->assertInstanceOf(Iframe_Me_Renderer::class, $renderer);

        /**
         * Test relative url init
         */
        $renderer = new Iframe_Me_Renderer('subhkamnaye!');
        $this->assertInstanceOf(Iframe_Me_Renderer::class, $renderer);
    }


    public function test_iframe_me_output_html()
    {
        // Must have iframe tag at starting with src attribute
        $renderer = new Iframe_Me_Renderer('https://localhost');
        $output   = $renderer->output();
        $prefix   = '<iframe src=\'https://localhost\'';
        $this->assertStringStartsWith(
            $prefix,
            $output,
            'Output HTML must start with iframe tag followed by src attribute'
        );

        // Output src url is properly escaped
        $renderer = new Iframe_Me_Renderer('http://localhost?q=<h3>Hello from XSS"</h3>');
        $output   = $renderer->output();
        $escaped_url   = esc_url('http://localhost?q=<h3>Hello from XSS"</h3>');
        $this->assertStringStartsWith(
            "<iframe src='{$escaped_url}'",
            $output,
            'Src attribute not escaped using esc_url'
        );

        // Attributes are properly escaped
        $renderer = new Iframe_Me_Renderer('http://localhost', [
            'height' => '<script>alert(\'hacked\')</script>'
        ]);

        $output   = $renderer->output();
        $expected_output = 'height=\'&lt;script&gt;alert(&#039;hacked&#039;)&lt;/script&gt;\'';
        $escaped_attribute_found = strpos($output, $expected_output) !== false;
        $this->assertTrue($escaped_attribute_found, 'Attributes not escaped using esc_attr.');
    }

    public function test_class_attribute(){
        $renderer = new Iframe_Me_Renderer('https://localhost');
        $output   = $renderer->output();
        $pattern = "/class='(.|)+'/";
        $this->assertEquals(1, preg_match($pattern, $output), 'Class attribute not found on iframe');

        $pattern = "/class='iframe-me'/";
        $this->assertEquals(1, preg_match($pattern, $output), 'Default Class value invalid');

        $renderer = new Iframe_Me_Renderer('https://localhost', [
            'class' => 'new class'
        ]);
        $output   = $renderer->output();
        $pattern = "/class='.+ new class'/";
        $this->assertEquals(1, preg_match($pattern, $output), 'Custom class value not reflected in output');

        $renderer = new Iframe_Me_Renderer('https://localhost', [
            'class' => ' new class '
        ]);
        $output   = $renderer->output();
        $pattern = "/class='.+ new class'/";
        $this->assertEquals(1, preg_match($pattern, $output), 'Class attribute value not trimmed');
    }

    public function test_throws_exception_when_return_request_returns_wp_error()
    {
        $this->expectException(Iframe_Me_Request_Exception::class);

        add_filter('pre_http_request', function () {
            return new WP_Error();
        });

        $renderer = new Iframe_Me_Renderer('https://example.com');
        $renderer->output();
    }

    public function test_throws_exception_when_request_returns_4xx_status_code()
    {
        $this->expectException(Iframe_Me_Request_Exception::class);

        add_filter('pre_http_request', function () {
            return [
                'response' => [
                    'code' => 400
                ]
            ];
        });

        $renderer = new Iframe_Me_Renderer('https://example.com');
        $renderer->output();
    }

    public function test_throws_iframe_me_request_exception_on_500_status_code()
    {
        $this->expectException(Iframe_Me_Request_Exception::class);

        add_filter('pre_http_request', function () {
            return [
                'response' => [
                    'code' => 505
                ]
            ];
        });

        $renderer = new Iframe_Me_Renderer('https://example.com');
        $renderer->output();
    }
}
