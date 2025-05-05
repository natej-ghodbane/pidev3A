<?php

namespace App\Service;
use Nucleos\DompdfBundle\Factory\DompdfFactoryInterface;
use Nucleos\DompdfBundle\Wrapper\DompdfWrapperInterface;
use Twig\Environment;

final class MyService
{   private DompdfWrapperInterface $wrapper;
    private Environment $twig;
    public function __construct(DompdfWrapperInterface $wrapper , Environment $twig)
    {
        $this->wrapper = $wrapper;
        $this->twig = $twig;
    }
   

    public function generatePdf(string $templatePath, array $parameters = []): string
    {
        $html = $this->twig->render($templatePath, $parameters);

        return $this->wrapper->getPdf($html, [
            'defaultFont' => 'Arial',
            'paper' => 'A4',
            'orientation' => 'portrait',
        ]);
    }
    
}



