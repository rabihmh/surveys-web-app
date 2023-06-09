import PageComponent from "../components/PageComponent";
import {useStateContext} from "../context/ContextProvider.jsx";
import SurveyListItem from "../components/SurveyListItem";
import TButton from "../components/core/TButton";
import {PlusCircleIcon} from "@heroicons/react/20/solid/index.js";

export default function Surveys() {
  const {surveys} = useStateContext()
  console.log(surveys);
  const onDeleteClick = () => {
    console.log('One Delete Click')
  }
  return (
    <PageComponent title="Surveys"
                   buttons={
                     (
                       <TButton color="green" to="/surveys/create">
                         <PlusCircleIcon className="h-6 w-6 mr-2"/>
                         Create new
                       </TButton>
                     )
                   }>
      <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 md:grid-cols-3">
        {surveys.map(survey => (
          <SurveyListItem survey={survey} key={survey.id} onDeleteClick={onDeleteClick}/>
        ))}

      </div>
    </PageComponent>
  );
}

