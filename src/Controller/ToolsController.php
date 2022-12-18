<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Portfolio;
use App\Entity\Tools;
use App\Form\ToolsType;
use App\Importer\ImporterIB;
use App\Importer\ImporterOFX;

/**
 * @Route("/tools")
 */
class ToolsController extends AbstractController
{

    protected $em;

    /**
     * constructor.
     *
     * @param EntityManagerInterface $em
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(EntityManagerInterface $em)
    {
        //    	parent::__construct();
        $this->em = $em;
    }

    /**
     * Try to use $file->getClientOriginalExtension() instead of $file->guessExtension()
     * I think you can just use the $this->getParameter('upload_directory') . $fileName
     * @Route("/portfolio/{id}/upload", name="portfolio_tools_upload")
     */
    public function upload(Request $request, Portfolio $portfolio): Response
    {
        $upload = new Tools();
        $formUpload = $this->createForm(ToolsType::class, $upload);
        $formUpload->handleRequest($request);
        if ($formUpload->isSubmitted() && $formUpload->isValid()) {
            /** @var UploadedFile $csvFile */
            $csvFile = $formUpload->get('csvFile')->getData();
            // this condition is needed because the 'portfolio' field is not required
            // so the file must be processed only when a file is uploaded
            if ($csvFile) {
                $importer = new ImporterIB($this->em);
                $records = $importer->start($csvFile->getRealPath());
                foreach ($records as $offset => $record) {
                    $importer->processOneRecord($portfolio, $record);
                }
            }
            /** @var UploadedFile $ofxFile */
            $ofxFile = $formUpload->get('ofxFile')->getData();
            // this condition is needed because the 'portfolio' field is not required
            // so the file must be processed only when a file is uploaded
            if ($ofxFile) {
                $importer = new ImporterOFX($this->em);
                $records = $importer->start($ofxFile->getRealPath());
                foreach ($records as $offset => $record) {
                    $importer->processOneRecord($portfolio, $record);
                }
            }
            if ($csvFile || $ofxFile) {
                return $this->redirectToRoute('portfolio_show', ['id' => $portfolio->getId()]);
            }
        }
        return $this->render('tools/upload.html.twig', [
            'portfolio' => $portfolio,
            'formUpload' => $formUpload->createView()
        ]);
    }

}