<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Survey\SurveyStoreRequest;
use App\Http\Requests\Survey\SurveyUpdateRequest;
use App\Http\Resources\Survey\SurveyResource;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\SurveyQuestionAnswer;
use App\Traits\SaveImage;
use DateTime;
use Exception;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class SurveyController extends Controller
{
    use SaveImage;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        return SurveyResource::collection(
            Survey::query()->where('user_id', $user->id)->orderBy('created_at', 'desc')->paginate('10'));
    }

    /**
     * Store a newly created resource in storage.
     * @throws Exception
     */
    public function store(SurveyStoreRequest $request): SurveyResource
    {
        $data = $request->validated();
        if (isset($data['image'])) {
            $relativePath = $this->upload($data['image']);
            $data['image'] = $relativePath;
        }
        $survey = Survey::create($data);
        foreach ($data['questions'] as $question) {
            $question['survey_id'] = $survey->id;
            $survey->createQuestion($question);
        }

        return new SurveyResource($survey);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Survey $survey): SurveyResource
    {
        $user = $request->user();
        if ($user->id !== $survey->user_id) {
            return abort(403, 'Unauthorized action');
        }
        return new SurveyResource($survey);
    }

    /**
     * Update the specified resource in storage.
     * @throws Exception
     */
    public function update(SurveyUpdateRequest $request, Survey $survey): SurveyResource
    {
        $data = $request->validated();

        if (isset($data['image'])) {
            $relativePath = $this->upload($data['image']);
            $data['image'] = $relativePath;
            if ($survey->image) {
                $absolutePath = public_path($survey->image);
                File::delete($absolutePath);
            }
        }
        $survey->update($data);

        $existingIds = $survey->questions()->pluck('id')->toArray();
        $newIds = Arr::pluck($data['questions'], 'id');
        $toDelete = array_diff($existingIds, $newIds);
        $toAdd = array_diff($newIds, $existingIds);

        SurveyQuestion::destroy($toDelete);
        foreach ($data['questions'] as $question) {
            if (in_array($question['id'], $toAdd)) {
                $question['survey_id'] = $survey->id;
                $survey->createQuestion($question);
            }
        }

        $questionMap = collect($data['questions'])->keyBy('id');
        foreach ($survey->questions as $question) {
            if (isset($questionMap[$question->id])) {
                $survey->updateQuestion($question, $questionMap[$question->id]);
            }
        }

        return new SurveyResource($survey);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Survey $survey, Request $request): Application|Response|\Illuminate\Contracts\Foundation\Application|ResponseFactory
    {
        $user = $request->user();
        if ($user->id !== $survey->user_id) {
            return abort(403, 'Unauthorized action.');
        }

        $survey->delete();

        if ($survey->image) {
            $absolutePath = public_path($survey->image);
            File::delete($absolutePath);
        }

        return response('', 204);
    }

    /**
     * @throws Exception
     */
    public function getBySlug(Survey $survey): Application|Response|SurveyResource|\Illuminate\Contracts\Foundation\Application|ResponseFactory
    {
        if (!$survey->status) {
            return response("", 404);
        }

        $currentDate = new DateTime();
        $expireDate = new DateTime($survey->expire_date);
        if ($currentDate > $expireDate) {
            return response("", 404);
        }

        return new SurveyResource($survey);
    }

    public function storeAnswer(Request $request, Survey $survey): Application|Response|\Illuminate\Contracts\Foundation\Application|ResponseFactory
    {
        $request->validate([
            'answers' => 'required'
        ]);
        $validated = $request->all();

        $surveyAnswer = SurveyAnswer::create([
            'survey_id' => $survey->id,
            'start_date' => date('Y-m-d H:i:s'),
            'end_date' => date('Y-m-d H:i:s'),
        ]);

        foreach ($validated['answers'] as $questionId => $answer) {
            $question = SurveyQuestion::where(['id' => $questionId, 'survey_id' => $survey->id])->get();
            if (!$question) {
                return response("Invalid question ID: \"$questionId\"", 400);
            }

            $data = [
                'survey_question_id' => $questionId,
                'survey_answer_id' => $surveyAnswer->id,
                'answer' => is_array($answer) ? json_encode($answer) : $answer
            ];

            $questionAnswer = SurveyQuestionAnswer::create($data);
        }

        return response("", 201);
    }

}
