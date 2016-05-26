from __future__ import print_function
import sklearn
import json
import sys
from sys import argv
# Import all of the scikit learn stuff

from sklearn.decomposition import TruncatedSVD
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.feature_extraction.text import CountVectorizer
from sklearn.preprocessing import Normalizer
from sklearn import metrics
from sklearn.cluster import KMeans, MiniBatchKMeans
import pandas as pd
import warnings
# Suppress warnings from pandas library
warnings.filterwarnings("ignore", category=DeprecationWarning, module="pandas", lineno=570)

import numpy
#file reading
docs=[]
filename=sys.argv[1]
txt = open(filename, "r")
count=int(sys.argv[2])
with open(filename) as line:
    i=0
    while i < count:
        docs.append(line.readline())
        i+=1
vectorizer = CountVectorizer(min_df = 1, stop_words = 'english')
#vectorizer = TfidfVectorizer(min_df=0.5, stop_words='english')
dtm = vectorizer.fit_transform(docs)
pd.DataFrame(dtm.toarray(),index=docs,columns=vectorizer.get_feature_names()).head(10)

vectorizer.get_feature_names()

lsa = TruncatedSVD(2, algorithm = 'arpack')
dtm_lsa = lsa.fit_transform(dtm)
dtm_lsa = Normalizer(copy=False).fit_transform(dtm_lsa)

similarity = numpy.asarray(numpy.asmatrix(dtm_lsa) * numpy.asmatrix(dtm_lsa).T)
pd.DataFrame(similarity,index=docs, columns=docs).head(10)
i=1
weight=0

while i < count:
    weight+=similarity[count-1][i]
    i+=1
weight=100*(weight/(count-1))
print (weight)
